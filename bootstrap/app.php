<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Sync OTO shipments every 30 minutes
        $schedule->command('oto:sync-shipments')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetLocaleFromAcceptLanguage::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, $request) {
            // Custom API exception handling
            if ($request->expectsJson()) {
                if ($exception instanceof ValidationException) {
                    return Response::error(
                        __('Validation failed'),
                        $exception->errors(),
                        422
                    );
                }

                if ($exception instanceof AuthenticationException) {
                    return Response::error(
                        __('Unauthenticated'),
                        [],
                        401
                    );
                }

                if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return Response::error(
                        __('Unauthorized access'),
                        [],
                        403
                    );
                }

                if ($exception instanceof NotFoundHttpException) {
                    return Response::error(
                        __('Resource not found'),
                        [],
                        404
                    );
                }

                if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    return Response::error(
                        __('Method not allowed'),
                        [],
                        405
                    );
                }

                // Handling Throttle Limit Exceeded (Too Many Requests)
                if ($exception instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                    return Response::error(
                        __('Too many requests'),
                        [],
                        429
                    );
                }

                if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    return Response::error(
                        $exception->getMessage() ?: __('Internal server error'),
                        [],
                        $exception->getStatusCode()
                    );
                }

                return Response::error(
                    $exception->getMessage() ?: __('Internal server error'),
                    [],
                    500
                );
            }

            return null;
        });
    })->create();
