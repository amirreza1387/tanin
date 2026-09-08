<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Events\ArticleUnpublished;
use App\Events\BreakingNewsChanged;
use Illuminate\Support\Facades\Cache;
use App\Services\PublicContentCache;

class InvalidateArticleCaches
{
    public function handle(ArticlePublished|ArticleUnpublished|BreakingNewsChanged $event): void
    {
        Cache::forget('articles:breaking');
        Cache::forget('articles:most-viewed:today');
        Cache::forget('articles:most-viewed:week');
        app(PublicContentCache::class)->forgetArticleLists();
        Cache::forget('management:analytics:overview');
    }
}
