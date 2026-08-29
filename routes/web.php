<?php

use App\Services\SitemapService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::view('/{any}', 'app')->where('any', '^(?!sitemap\.xml).*$');

Route::get('/sitemap.xml', function () {
    if (! file_exists(public_path('sitemap.xml'))) {
        app(SitemapService::class)->refresh();
    }

    return response()->file(public_path('sitemap.xml'));
});
