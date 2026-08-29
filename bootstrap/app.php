<?php

use App\Jobs\FlushViewCounter;
use App\Jobs\PublishScheduledArticles;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(new PublishScheduledArticles)->everyMinute();
        $schedule->job(new FlushViewCounter)->everyFiveMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('اطلاعات ارسال‌شده معتبر نیست.', $exception->errors());
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Ø´Ù…Ø§ Ø§Ø¬Ø§Ø²Ù‡ Ø§Ù†Ø¬Ø§Ù… Ø§ÛŒÙ† Ø¹Ù…Ù„ÛŒØ§Øª Ø±Ø§ Ù†Ø¯Ø§Ø±ÛŒØ¯.', [], 403);
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('برای انجام این عملیات وارد حساب کاربری شوید.', [], 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('رکورد موردنظر پیدا نشد.', [], 404);
            }
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($exception->getMessage() ?: 'Ø¯Ø±Ø®ÙˆØ§Ø³Øª Ù†Ø§Ù…Ø¹ØªØ¨Ø± Ø§Ø³Øª.', [], $exception->getStatusCode());
            }
        });
    })->create();
