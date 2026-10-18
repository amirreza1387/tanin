<?php

use App\Services\SitemapService;
use App\Http\Controllers\PublicArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->view('app')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
});

Route::get('/articles/{slug}', PublicArticleController::class);

Route::get('/{any}', function () {
    return response()->view('app')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
})->where('any', '^(?!sitemap\.xml).*$');

Route::get('/sitemap.xml', function () {
    return response(app(SitemapService::class)->xml(), 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
});
