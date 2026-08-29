<?php

namespace App\Repositories\Contracts;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface ArticleRepositoryInterface
{
    public function published(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findPublishedBySlug(string $slug): Article;

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function breaking(int $limit = 10);

    public function queryForManagement(?int $authorId = null): Builder;
}
