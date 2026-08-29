<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Enums\CommentStatus;
use App\Events\CommentApproved;
use App\Http\Requests\ModerateCommentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Article;
use App\Models\Comment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Article $article)
    {
        abort_unless($article->status === ArticleStatus::PUBLISHED, 404, __('messages.published_article_only'));

        $comments = $article->comments()
            ->where('status', CommentStatus::APPROVED)
            ->whereNull('parent_id')
            ->with(['user', 'replies' => fn ($query) => $query->where('status', CommentStatus::APPROVED)->with('user')])
            ->latest()
            ->get();

        return ApiResponse::success(CommentResource::collection($comments));
    }

    public function store(StoreCommentRequest $request, Article $article)
    {
        abort_unless($article->status === ArticleStatus::PUBLISHED, 404, __('messages.published_article_only'));

        $comment = $article->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $request->validated('parent_id'),
            'body' => $request->validated('body'),
            'status' => CommentStatus::PENDING,
        ]);

        return ApiResponse::success(new CommentResource($comment->load('user')), [], 201);
    }

    public function moderation(Request $request)
    {
        $this->authorize('moderateAny', Comment::class);
        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $comments = Comment::query()->with(['user', 'article'])->latest()->paginate($perPage);

        return ApiResponse::paginated(CommentResource::class, $comments);
    }

    public function moderate(ModerateCommentRequest $request, Comment $comment)
    {
        $this->authorize('moderate', $comment);
        $status = CommentStatus::from($request->validated('status'));
        $comment->update(['status' => $status]);
        if ($status === CommentStatus::APPROVED) {
            CommentApproved::dispatch($comment->fresh());
        }

        return ApiResponse::success(new CommentResource($comment->fresh('user')));
    }
}
