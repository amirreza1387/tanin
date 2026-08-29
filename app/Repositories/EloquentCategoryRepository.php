<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function tree(): Collection
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return $this->loadChildren($categories);
    }

    public function findBySlug(string $slug): Category
    {
        return Category::query()
            ->with(['children', 'articles' => fn ($query) => $query->where('status', 'published')])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function loadChildren(Collection $categories): Collection
    {
        $categories->load('children');

        foreach ($categories as $category) {
            if ($category->children->isNotEmpty()) {
                $this->loadChildren($category->children);
            }
        }

        return $categories;
    }
}
