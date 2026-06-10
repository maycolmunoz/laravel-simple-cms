<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // The Vite dev server (npm run dev) loads HMR assets and a websocket from a separate
        // origin with inline module scripts, which any sane CSP blocks. Skip CSP only while the
        // hot file exists — never in a built/production deploy, where these tests run too.
        if (! is_file(public_path('hot'))) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request));
        }

        // Only advertise HSTS over HTTPS so local HTTP development is unaffected.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Build the Content-Security-Policy. The Filament admin panel relies on Alpine.js,
     * which requires 'unsafe-eval' and inline handlers, so the /admin path gets a more
     * permissive policy than the public frontend (whose scripts are all Vite-bundled).
     */
    protected function contentSecurityPolicy(Request $request): string
    {
        $scriptSrc = $request->is('admin', 'admin/*')
            ? "script-src 'self' 'unsafe-inline' 'unsafe-eval'"
            : "script-src 'self'";

        return implode('; ', [
            "default-src 'self'",
            $scriptSrc,
            // Purified content and Tailwind utilities can emit inline style attributes.
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
