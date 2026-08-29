<?php

use App\Models\Article;
use App\Services\ViewCounterService;

it('flushes buffered views to article and daily statistics', function (): void {
    $article = Article::factory()->create();
    $counter = app(ViewCounterService::class);

    $counter->increment($article);
    $counter->increment($article);
    $counter->increment($article);

    expect($counter->flush())->toBe(3)
        ->and($article->fresh()->views)->toBe(3)
        ->and($article->fresh()->viewStats()->first()->views)->toBe(3);
});
