<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\ArticleResource;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Support\ApiResponse;

class SearchController extends Controller
{
    public function __construct(private readonly ArticleRepositoryInterface $articles) {}

    public function __invoke(SearchRequest $request)
    {
        return ApiResponse::paginated(
            ArticleResource::class,
            $this->articles->search($request->validated(), $request->integer('per_page', 15)),
        );
    }
}
