<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Events\ArticleUnpublished;
use App\Services\SitemapService;

class RefreshSitemap
{
    public function __construct(private readonly SitemapService $sitemapService) {}

    public function handle(ArticlePublished|ArticleUnpublished $event): void
    {
        $this->sitemapService->refresh();
    }
}
