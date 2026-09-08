<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MostViewedController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
    Route::get('breaking-news', [ArticleController::class, 'activeBreaking']);
    Route::get('featured-article', [ArticleController::class, 'featured']);
    Route::get('editor-picks', [ArticleController::class, 'editorPicks']);
    Route::get('home-sections', [ArticleController::class, 'homeSections']);
    Route::get('most-viewed', MostViewedController::class);
    Route::get('search', SearchController::class);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::get('tags', [TagController::class, 'index']);
    Route::get('tags/{slug}', [TagController::class, 'show']);
    Route::get('advertisements', [AdvertisementController::class, 'index']);
    Route::get('articles/{article}/comments', [CommentController::class, 'index']);
    Route::post('telemetry', [TelemetryController::class, 'store'])->middleware('throttle:30,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/password', [AuthController::class, 'updatePassword']);

        Route::post('articles', [ArticleController::class, 'store'])->name('management.articles.store');
        Route::get('management/articles', [ArticleController::class, 'management'])->name('management.articles.index');
        Route::get('management/analytics', AnalyticsController::class)->name('management.analytics');
        Route::put('articles/{article}', [ArticleController::class, 'update'])->name('management.articles.update');
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('management.articles.destroy');
        Route::post('articles/{article}/publish', [ArticleController::class, 'publish'])->name('management.articles.publish');
        Route::post('articles/{article}/schedule', [ArticleController::class, 'schedule'])->name('management.articles.schedule');
        Route::post('articles/{article}/revert', [ArticleController::class, 'revert'])->name('management.articles.revert');
        Route::patch('articles/{article}/breaking', [ArticleController::class, 'breaking'])->name('management.articles.breaking');
        Route::patch('articles/{article}/featured', [ArticleController::class, 'featuredUpdate'])->name('management.articles.featured');

        Route::post('articles/{article}/comments', [CommentController::class, 'store']);
        Route::get('media', [MediaController::class, 'index']);
        Route::post('media', [MediaController::class, 'store']);
        Route::get('management/advertisements', [AdvertisementController::class, 'index'])->name('management.advertisements.index');
        Route::post('management/advertisements', [AdvertisementController::class, 'store']);
        Route::put('management/advertisements/{advertisement}', [AdvertisementController::class, 'update']);
        Route::delete('management/advertisements/{advertisement}', [AdvertisementController::class, 'destroy']);

        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
        Route::post('tags', [TagController::class, 'store']);
        Route::put('tags/{tag}', [TagController::class, 'update']);
        Route::delete('tags/{tag}', [TagController::class, 'destroy']);

        Route::get('management/comments', [CommentController::class, 'moderation']);
        Route::patch('management/comments/{comment}', [CommentController::class, 'moderate']);
        Route::get('management/users', [UserController::class, 'index']);
        Route::patch('management/users/{user}', [UserController::class, 'update']);
    });
});
