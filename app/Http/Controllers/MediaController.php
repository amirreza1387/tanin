<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Article;
use App\Models\Media;
use App\Services\MediaService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request)
    {
        $this->authorize('create', Media::class);

        $media = Media::query()->latest()->paginate(min($request->integer('per_page', 30), 100));

        return ApiResponse::paginated(MediaResource::class, $media);
    }

    public function store(UploadMediaRequest $request)
    {
        $this->authorize('create', Media::class);

        if ($request->validated('article_id')) {
            $article = Article::query()->findOrFail($request->validated('article_id'));
            $this->authorize('update', $article);
        }

        $media = $this->media->upload($request->user(), $request->file('file'));
        if (isset($article)) {
            $media->update([
                'mediable_type' => Article::class,
                'mediable_id' => $article->id,
            ]);
        }

        return ApiResponse::success(new MediaResource($media->fresh()), [], 201);
    }
}
