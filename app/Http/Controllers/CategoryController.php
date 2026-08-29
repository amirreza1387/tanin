<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\SlugService;
use App\Support\ApiResponse;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly SlugService $slugs,
    ) {}

    public function index()
    {
        return ApiResponse::success(CategoryResource::collection($this->categories->tree()));
    }

    public function show(string $slug)
    {
        return ApiResponse::success(new CategoryResource($this->categories->findBySlug($slug)));
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->authorize('manage', Category::class);
        $data = $request->validated();
        $data['slug'] = $this->slugs->make($data['name'], Category::class);
        $category = Category::create($data);

        return ApiResponse::success(new CategoryResource($category), [], 201);
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        $this->authorize('manage', Category::class);
        $data = $request->validated();
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->slugs->make($data['name'], Category::class, $category->id);
        }
        $category->update($data);

        return ApiResponse::success(new CategoryResource($category->fresh()));
    }

    public function destroy(Category $category)
    {
        $this->authorize('manage', Category::class);
        $category->delete();

        return ApiResponse::success(['message' => __('messages.category_deleted')]);
    }
}
