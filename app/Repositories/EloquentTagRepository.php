<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentTagRepository implements TagRepositoryInterface
{
    public function all(): Collection
    {
        return Tag::query()->withCount('articles')->orderBy('name')->get();
    }

    public function findBySlug(string $slug): Tag
    {
        return Tag::query()
            ->with(['articles' => fn ($query) => $query->where('status', 'published')])
            ->where('slug', $slug)
            ->firstOrFail();
    }
}
