<?php

use App\Services\SitemapService;
use App\Http\Controllers\PublicArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::get('/articles/{slug}', PublicArticleController::class);

Route::view('/{any}', 'app')->where('any', '^(?!sitemap\.xml).*$');

Route::get('/sitemap.xml', function () {
    return response(app(SitemapService::class)->xml(), 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
});
