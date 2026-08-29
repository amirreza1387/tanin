<?php

namespace App\Repositories;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function published(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['author', 'category', 'tags', 'featuredMedia'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->latest('published_at');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): Article
    {
        return Article::query()
            ->with(['author', 'category', 'tags', 'featuredMedia', 'comments' => fn ($query) => $query
                ->where('status', 'approved')
                ->whereNull('parent_id')
                ->with(['user', 'replies.user']),
            ])
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['author', 'category', 'tags', 'featuredMedia'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->latest('published_at');

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            if (Article::query()->getConnection()->getDriverName() === 'mysql') {
                $query->whereFullText(['title', 'lead', 'body'], $term);
            } else {
                $like = '%'.$term.'%';
                $query->where(fn ($builder) => $builder
                    ->where('title', 'like', $like)
                    ->orWhere('lead', 'like', $like)
                    ->orWhere('body', 'like', $like));
            }
        }

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function breaking(int $limit = 10)
    {
        return Article::query()
            ->with(['category', 'featuredMedia'])
            ->where('status', 'published')
            ->where('is_breaking', true)
            ->where(function ($query): void {
                $query->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function queryForManagement(?int $authorId = null): Builder
    {
        return Article::query()
            ->with(['author', 'category', 'tags', 'featuredMedia'])
            ->when($authorId, fn ($query) => $query->where('author_id', $authorId))
            ->latest();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when($filters['category_id'] ?? null, fn ($builder, $categoryId) => $builder->where('category_id', $categoryId));
        $query->when($filters['category_slug'] ?? null, fn ($builder, $slug) => $builder->whereHas('category', fn ($category) => $category->where('slug', $slug)));
        $query->when($filters['from'] ?? null, fn ($builder, $date) => $builder->whereDate('published_at', '>=', Carbon::parse($date)));
        $query->when($filters['to'] ?? null, fn ($builder, $date) => $builder->whereDate('published_at', '<=', Carbon::parse($date)));
    }
}
