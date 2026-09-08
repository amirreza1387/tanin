<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\SlugService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly SlugService $slugs,
    ) {}

    public function index()
    {
        return ApiResponse::success(CategoryResource::collection(Cache::remember('public:categories', 3600, fn () => $this->categories->tree())));
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
        Cache::forget('public:categories');

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
        Cache::forget('public:categories');

        return ApiResponse::success(new CategoryResource($category->fresh()));
    }

    public function destroy(Category $category)
    {
        $this->authorize('manage', Category::class);
        $category->delete();
        Cache::forget('public:categories');

        return ApiResponse::success(['message' => __('messages.category_deleted')]);
    }
}
