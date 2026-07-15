<?php

use App\Http\Middleware\EnsureActiveAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\VerifyTicketPalEvent;
use App\Http\Middleware\VerifyTicketPalSecret;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ticketpal.secret' => VerifyTicketPalSecret::class,
            'ticketpal.event' => VerifyTicketPalEvent::class,
            'admin.active' => EnsureActiveAdmin::class,
            'super_admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
