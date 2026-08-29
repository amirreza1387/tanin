<?php

use App\Models\Tag;
use App\Services\SlugService;

it('normalizes Persian slug input', function (): void {
    $slug = app(SlugService::class)->normalize('خبر  ي كِ  ویژه!');

    expect($slug)->toBe('خبر-ی-ک-ویژه');
});

it('guarantees a unique slug with a numeric suffix', function (): void {
    Tag::factory()->create(['slug' => 'خبر-ویژه']);

    $slug = app(SlugService::class)->make('خبر ویژه', Tag::class);

    expect($slug)->toBe('خبر-ویژه-2');
});
