<?php

use App\Jobs\OutboxDispatcherJob;
use App\Jobs\RecoverStuckWebhookJobs;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('bookings:cancel-overdue')->hourly();
        $schedule->job(new OutboxDispatcherJob)->everyMinute()->withoutOverlapping(5);
        $schedule->job(new RecoverStuckWebhookJobs)->everyFiveMinutes();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof \InvalidArgumentException && str_contains($e->getMessage(), 'is not a refund')) {
                Log::warning('Refund resource request rejected.', [
                    'exception' => $e,
                    'path' => $request->path(),
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Refund receipt is unavailable.',
                    ], 404);
                }

                return back()->withErrors('Refund receipt is unavailable.');
            }

            if ($e instanceof \InvalidArgumentException) {
                Log::warning('Invalid application request rejected.', [
                    'exception' => $e,
                    'path' => $request->path(),
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The submitted request could not be processed.',
                    ], 422);
                }

                return back()->withErrors('The submitted request could not be processed.');
            }
        });
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureUserIsNotBanned::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->create();
