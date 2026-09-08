<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    public function __invoke()
    {
        $this->authorize('manage', Article::class);

        $data = Cache::remember('management:analytics:overview', 300, function (): array {
            $from = today()->subDays(13);
            $daily = ArticleViewStat::query()
                ->selectRaw('view_date, SUM(views) as views')
                ->whereDate('view_date', '>=', $from)
                ->groupBy('view_date')
                ->orderBy('view_date')
                ->get()
                ->keyBy(fn ($row) => $row->view_date->toDateString());

            $series = collect(range(13, 0))->map(function (int $daysAgo) use ($daily): array {
                $date = today()->subDays($daysAgo)->toDateString();
                return ['date' => $date, 'views' => (int) ($daily->get($date)?->views ?? 0)];
            });

            return [
                'totals' => [
                    'articles' => Article::query()->count(),
                    'published' => Article::query()->where('status', 'published')->count(),
                    'views_14d' => $series->sum('views'),
                ],
                'series' => $series,
                'top_articles' => Article::query()
                    ->with('category')
                    ->orderByDesc('views')
                    ->limit(5)
                    ->get(['id', 'title', 'slug', 'category_id', 'views'])
                    ->map(fn (Article $article) => [
                        'id' => $article->id, 'title' => $article->title, 'slug' => $article->slug,
                        'views' => $article->views, 'category' => $article->category?->name,
                    ]),
            ];
        });

        return ApiResponse::success($data);
    }
}
