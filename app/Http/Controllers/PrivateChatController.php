<?php

namespace App\Http\Controllers;

use App\Models\PrivateConversation;
use App\Models\PrivateMessage;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class PrivateChatController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        $profile = $user->privateChatProfile;
        return ApiResponse::success([
            'nickname' => $profile?->nickname ?: $user->name,
            'avatar_url' => $profile?->avatar_path ? '/private-chat/profiles/'.$user->id.'/avatar?v='.$profile->updated_at->timestamp : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function presence(Request $request)
    {
        $user = $request->user();
        $profile = $user->privateChatProfile()->firstOrNew();
        if (!$profile->exists) $profile->nickname = $user->name;
        $profile->last_seen_at = now();
        $profile->save();

        return ApiResponse::success(['last_seen_at' => $profile->last_seen_at])
            ->header('Cache-Control', 'private, no-store');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'nickname' => ['required', 'string', 'max:32'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=2048,max_height=2048'],
            'clear_avatar' => ['sometimes', 'boolean'],
        ]);
        $profile = $request->user()->privateChatProfile()->firstOrNew();
        $oldPath = $profile->avatar_path;
        $profile->nickname = trim($data['nickname']);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = 'private-chat/profiles/'.$request->user()->id.'/'.Str::uuid().'.enc';
            Storage::disk('local')->put($path, Crypt::encryptString(file_get_contents($file->getRealPath())));
            $profile->avatar_path = $path;
            $profile->avatar_mime = $file->getMimeType() ?: 'image/jpeg';
        } elseif ($request->boolean('clear_avatar')) {
            $profile->avatar_path = null;
            $profile->avatar_mime = null;
        }

        $profile->save();
        if ($oldPath && $oldPath !== $profile->avatar_path) Storage::disk('local')->delete($oldPath);

        return ApiResponse::success([
            'nickname' => $profile->nickname,
            'avatar_url' => $profile->avatar_path ? '/private-chat/profiles/'.$request->user()->id.'/avatar?v='.$profile->updated_at->timestamp : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function profileAvatar(Request $request, User $user)
    {
        $profile = $user->privateChatProfile;
        abort_unless($profile?->avatar_path, 404);
        if (!$request->user()->isAdmin() && $request->user()->id !== $user->id) {
            abort_unless(PrivateConversation::query()->where(fn ($query) => $query
                ->where('user_one_id', $request->user()->id)->where('user_two_id', $user->id))
                ->orWhere(fn ($query) => $query
                    ->where('user_one_id', $user->id)->where('user_two_id', $request->user()->id))
                ->exists(), 403);
        }

        $bytes = Crypt::decryptString(Storage::disk('local')->get($profile->avatar_path));
        return response($bytes)->header('Content-Type', $profile->avatar_mime ?: 'application/octet-stream')
            ->header('Content-Disposition', 'inline; filename="chat-profile"')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, no-store');
    }

    public function users(Request $request)
    {
        $term = trim($request->query('q', ''));
        if (mb_strlen($term) < 2) return ApiResponse::success([])->header('Cache-Control', 'private, no-store');
        $users = User::query()->with('privateChatProfile')->whereKeyNot($request->user()->id)
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
            ->limit(20)->get(['id', 'name', 'email']);
        return ApiResponse::success($users->map(fn (User $user) => ['id' => $user->id, 'name' => $user->privateChatProfile?->nickname ?: $user->name, 'email' => $user->email]))->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = PrivateConversation::query()
            ->with(['userOne.privateChatProfile', 'userTwo.privateChatProfile', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')->where('sender_id', '!=', $user->id)]);
        $query->where(fn ($q) => $q->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id));
        return ApiResponse::success($query->latest('updated_at')->get()->map(fn ($c) => $this->conversationData($c, $user->id)))->header('Cache-Control', 'private, no-store');
    }

    public function start(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id', Rule::notIn([$request->user()->id])]]);
        $a = min($request->user()->id, (int) $data['user_id']);
        $b = max($request->user()->id, (int) $data['user_id']);
        try { $conversation = PrivateConversation::firstOrCreate(['user_one_id' => $a, 'user_two_id' => $b]); }
        catch (QueryException) { $conversation = PrivateConversation::where('user_one_id', $a)->where('user_two_id', $b)->firstOrFail(); }
        return ApiResponse::success(['id' => $conversation->id], [], 201)->header('Cache-Control', 'private, no-store');
    }

    public function messages(Request $request, PrivateConversation $conversation)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $conversation->includes($user->id), 403);
        $beforeId = $request->integer('before_id');
        $query = $conversation->messages()->with('sender.privateChatProfile');
        if ($beforeId > 0) $query->where('id', '<', $beforeId);
        $messages = $query->latest('id')->limit(100)->get()->reverse()->values();
        return ApiResponse::success([
            'messages' => $messages->map(fn ($m) => $this->messageData($m)),
            'next_before_id' => $messages->count() === 100 ? $messages->first()->id : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function markRead(Request $request, PrivateConversation $conversation)
    {
        $user = $request->user();
        abort_unless($conversation->includes($user->id), 403);
        $data = $request->validate(['message_id' => ['required', 'integer', 'min:1']]);
        $message = $conversation->messages()->whereKey($data['message_id'])->firstOrFail();
        $readAt = now();
        $conversation->messages()->whereNull('read_at')->where('sender_id', '!=', $user->id)
            ->where('id', '<=', $message->id)->update(['read_at' => $readAt]);

        return ApiResponse::success(['read_through_id' => $message->id, 'read_at' => $readAt])
            ->header('Cache-Control', 'private, no-store');
    }

    public function send(Request $request, PrivateConversation $conversation)
    {
        abort_unless($conversation->includes($request->user()->id), 403);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:10000', 'required_without:file'],
            'file' => ['nullable', 'file', 'max:25600', 'required_without:body'],
        ]);
        $attrs = ['conversation_id' => $conversation->id, 'sender_id' => $request->user()->id, 'body' => $data['body'] ?? null, 'reply_to_id' => $request->integer('reply_to_id') ?: null];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $contents = file_get_contents($file->getRealPath());
            $path = 'private-chat/'.Str::uuid().'.enc';
            Storage::disk('local')->put($path, Crypt::encryptString($contents));
            $attrs += ['attachment_path' => $path, 'attachment_name' => $file->getClientOriginalName(), 'attachment_mime' => $file->getMimeType() ?: 'application/octet-stream', 'attachment_size' => $file->getSize()];
        }
        $message = $conversation->messages()->create($attrs)->load('sender.privateChatProfile');
        $conversation->touch();
        return ApiResponse::success($this->messageData($message), [], 201)->header('Cache-Control', 'private, no-store');
    }

    public function editMessage(Request $request, PrivateMessage $message)
    {
        abort_unless($message->sender_id === $request->user()->id, 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        abort_unless(!$message->attachment_path, 422);
        $message->update(['body' => $data['body'], 'edited_at' => now()]);
        return ApiResponse::success($this->messageData($message->fresh('sender.privateChatProfile')));
    }

    public function deleteMessage(Request $request, PrivateMessage $message)
    {
        abort_unless($message->sender_id === $request->user()->id, 403);
        if ($message->attachment_path) Storage::disk('local')->delete($message->attachment_path);
        $message->delete();
        return ApiResponse::success(['deleted' => true]);
    }

    public function togglePin(Request $request, PrivateMessage $message)
    {
        abort_unless($message->conversation->includes($request->user()->id), 403);
        $message->pinned_at = $message->pinned_at ? null : now();
        $message->save();
        return ApiResponse::success(['pinned' => (bool) $message->pinned_at, 'pinned_at' => $message->pinned_at]);
    }

    public function attachment(Request $request, PrivateMessage $message)
    {
        $conversation = $message->conversation;
        abort_unless($request->user()->isAdmin() || $conversation->includes($request->user()->id), 403);
        abort_unless($message->attachment_path, 404);
        $encrypted = Storage::disk('local')->get($message->attachment_path);
        abort_unless(is_string($encrypted) && $encrypted !== '', 404);
        try {
            $bytes = Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            abort(404);
        }
        $filename = preg_replace('/[\x00-\x1F\x7F"\\\\]/u', '_', basename($message->attachment_name ?: 'download')) ?: 'download';
        return response($bytes)->header('Content-Type', $message->attachment_mime)->header('Content-Disposition', 'attachment; filename="'.$filename.'"')->header('X-Content-Type-Options', 'nosniff')->header('Cache-Control', 'private, no-store');
    }

    public function adminInbox(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $conversations = PrivateConversation::with([
            'userOne.privateChatProfile', 'userTwo.privateChatProfile',
            'messages' => fn ($q) => $q->with('sender.privateChatProfile')->latest()->limit(1),
        ])->latest('updated_at')->paginate(50);
        $items = $conversations->getCollection()->map(function ($conversation) {
            $latest = $conversation->messages->first();
            return [
                'id' => $conversation->id,
                'user' => ['name' => ($conversation->userOne->privateChatProfile?->nickname ?: $conversation->userOne->name).' · '.($conversation->userTwo->privateChatProfile?->nickname ?: $conversation->userTwo->name)],
                'last_message' => $latest ? $this->messageData($latest) : null,
            ];
        });
        return ApiResponse::success($items, ['current_page' => $conversations->currentPage(), 'last_page' => $conversations->lastPage(), 'total' => $conversations->total()])->header('Cache-Control', 'private, no-store');
    }

    private function conversationData(PrivateConversation $c, int $viewerId): array
    {
        $other = $c->user_one_id === $viewerId ? $c->userTwo : $c->userOne;
        $latest = $c->messages->first();
        $lastSeenAt = $other->privateChatProfile?->last_seen_at;
        return [
            'id' => $c->id,
            'user' => [
                'id' => $other->id,
                'name' => $other->privateChatProfile?->nickname ?: $other->name,
                'avatar_url' => $other->privateChatProfile?->avatar_path ? '/private-chat/profiles/'.$other->id.'/avatar?v='.$other->privateChatProfile->updated_at->timestamp : null,
                'is_online' => $lastSeenAt?->greaterThan(now()->subSeconds(45)) ?? false,
                'last_seen_at' => $lastSeenAt,
            ],
            'last_message' => $latest ? $this->messageData($latest) : null,
            'unread_count' => $c->unread_count ?? 0,
            'updated_at' => $c->updated_at,
        ];
    }

    private function messageData(PrivateMessage $m): array
    {
        return ['id' => $m->id, 'sender_id' => $m->sender_id, 'sender_name' => $m->sender?->privateChatProfile?->nickname ?: $m->sender?->name, 'body' => $m->body, 'attachment' => $m->attachment_path ? ['name' => $m->attachment_name, 'mime' => $m->attachment_mime, 'size' => $m->attachment_size, 'url' => '/private-chat/attachments/'.$m->id] : null, 'created_at' => $m->created_at, 'read_at' => $m->read_at, 'edited_at' => $m->edited_at, 'pinned_at' => $m->pinned_at, 'reply_to' => $m->replyTo ? ['id' => $m->replyTo->id, 'body' => $m->replyTo->body, 'sender_name' => $m->replyTo->sender?->privateChatProfile?->nickname ?: $m->replyTo->sender?->name] : null];
    }
}
