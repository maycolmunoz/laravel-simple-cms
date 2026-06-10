<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // No trusted proxies: X-Forwarded-* headers are ignored, so request()->ip()
        // can't be spoofed. If this app is ever put behind a load balancer/CDN, add a
        // config/trustedproxy.php with a 'proxies' key (read by the TrustProxies
        // middleware at request time) — do NOT call env() here, this closure runs
        // before the .env file is loaded.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
