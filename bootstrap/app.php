<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckEntityPermission;
use App\Http\Middleware\CheckSessionExpired;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.role' => CheckRole::class,
            'check.entity.permission' => CheckEntityPermission::class,
            'check.session' => CheckSessionExpired::class,
        ]);
        
        // Aplicar el middleware session check a todas las rutas protegidas por auth
        $middleware->prependToGroup('auth', CheckSessionExpired::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
