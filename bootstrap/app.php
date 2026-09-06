<?php

use App\Http\Middleware\AuthenticateProviderV2Request;
use App\Http\Middleware\EnsureActiveAdmin;
use App\Http\Middleware\EnsureMailgunWebhooksEnabled;
use App\Http\Middleware\EnsureProviderV2Enabled;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\VerifyMailgunWebhookSignature;
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
        // DigitalOcean App Platform terminates TLS at its load balancer. Trust
        // the forwarded scheme so Laravel generates HTTPS URLs and cookies.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'ticketpal.secret' => VerifyTicketPalSecret::class,
            'ticketpal.event' => VerifyTicketPalEvent::class,
            'admin.active' => EnsureActiveAdmin::class,
            'super_admin' => EnsureSuperAdmin::class,
            'provider.v2.enabled' => EnsureProviderV2Enabled::class,
            'provider.v2.auth' => AuthenticateProviderV2Request::class,
            'mailgun.webhooks.enabled' => EnsureMailgunWebhooksEnabled::class,
            'mailgun.webhooks.signature' => VerifyMailgunWebhookSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
