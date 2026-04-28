<?php

use App\Http\Middleware\ApiLoggerMiddleware;
use App\Http\Middleware\CheckGuestExpiration;
use App\Http\Middleware\IsAdminMiddleware;
use App\Http\Middleware\IsRoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            ApiLoggerMiddleware::class,
            StartSession::class,
        ]);

        $middleware->alias([
            'is_admin' => IsAdminMiddleware::class,
            'is_role' => IsRoleMiddleware::class,
            'check_guest' => CheckGuestExpiration::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
