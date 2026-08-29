<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Events\ArticleUnpublished;
use App\Events\BreakingNewsChanged;
use Illuminate\Support\Facades\Cache;

class InvalidateArticleCaches
{
    public function handle(ArticlePublished|ArticleUnpublished|BreakingNewsChanged $event): void
    {
        Cache::forget('articles:breaking');
        Cache::forget('articles:most-viewed:today');
        Cache::forget('articles:most-viewed:week');
    }
}
