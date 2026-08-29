<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleViewStat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ViewCounterService
{
    private const IDS_KEY = 'article-view-counter:ids';

    public function increment(Article $article): void
    {
        $key = $this->key($article->id);
        Cache::increment($key);
        $ids = Cache::get(self::IDS_KEY, []);
        $ids[] = $article->id;
        Cache::forever(self::IDS_KEY, array_values(array_unique($ids)));
    }

    public function flush(): int
    {
        $ids = Cache::pull(self::IDS_KEY, []);
        $flushed = 0;

        foreach ($ids as $articleId) {
            $key = $this->key((int) $articleId);
            $views = (int) Cache::pull($key, 0);
            if ($views === 0) {
                continue;
            }

            DB::transaction(function () use ($articleId, $views): void {
                Article::query()->whereKey($articleId)->increment('views', $views);
                $stat = ArticleViewStat::query()->firstOrNew(
                    ['article_id' => $articleId, 'view_date' => today()],
                );
                $stat->views = ($stat->views ?? 0) + $views;
                $stat->save();
            });
            $flushed += $views;
        }

        return $flushed;
    }

    private function key(int $articleId): string
    {
        return 'article-view-counter:'.$articleId;
    }
}
