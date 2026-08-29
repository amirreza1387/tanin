<?php

namespace App\Http\Controllers;

use App\Http\Requests\MostViewedRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Cache;

class MostViewedController extends Controller
{
    public function __invoke(MostViewedRequest $request)
    {
        $period = $request->validated('period', 'today');
        $cacheKey = 'articles:most-viewed:'.$period;

        $articles = Cache::remember($cacheKey, 300, function () use ($period) {
            $from = $period === 'today' ? today() : today()->subDays(6);
            $ids = ArticleViewStat::query()
                ->select('article_id')
                ->selectRaw('SUM(views) as total_views')
                ->whereDate('view_date', '>=', $from)
                ->groupBy('article_id')
                ->orderByDesc('total_views')
                ->limit(20)
                ->pluck('article_id');

            return Article::query()->with(['author', 'category', 'tags', 'featuredMedia'])
                ->whereIn('id', $ids)
                ->where('status', 'published')
                ->get()
                ->sortBy(fn (Article $article) => $ids->search($article->id))
                ->values();
        });

        return ApiResponse::success(ArticleResource::collection($articles));
    }
}
