<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // ← هذا مهم
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(StartSession::class); // ← هذا يفعّل الجلسات
        $middleware->append(HandleCors::class);
//        $middleware->append(\App\Http\Middleware\CorsMiddleware::class);

        $middleware->alias([
            'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'csrf' => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

