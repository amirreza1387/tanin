<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Services\SlugService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Cache;

class TagController extends Controller
{
    public function __construct(
        private readonly TagRepositoryInterface $tags,
        private readonly SlugService $slugs,
    ) {}

    public function index()
    {
        return ApiResponse::success(TagResource::collection(Cache::remember('public:tags', 3600, fn () => $this->tags->all())));
    }

    public function show(string $slug)
    {
        return ApiResponse::success(new TagResource($this->tags->findBySlug($slug)));
    }

    public function store(StoreTagRequest $request)
    {
        $this->authorize('manage', Tag::class);
        $data = $request->validated();
        $data['slug'] = $this->slugs->make($data['name'], Tag::class);

        $tag = Tag::create($data); Cache::forget('public:tags');
        return ApiResponse::success(new TagResource($tag), [], 201);
    }

    public function update(StoreTagRequest $request, Tag $tag)
    {
        $this->authorize('manage', Tag::class);
        $data = $request->validated();
        if (isset($data['name']) && $data['name'] !== $tag->name) {
            $data['slug'] = $this->slugs->make($data['name'], Tag::class, $tag->id);
        }
        $tag->update($data);
        Cache::forget('public:tags');

        return ApiResponse::success(new TagResource($tag->fresh()));
    }

    public function destroy(Tag $tag)
    {
        $this->authorize('manage', Tag::class);
        $tag->delete();
        Cache::forget('public:tags');

        return ApiResponse::success(['message' => __('messages.tag_deleted')]);
    }
}
