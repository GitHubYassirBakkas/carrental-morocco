<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add conservative browser security headers without breaking local HTTP development.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $this->applyContentSecurityPolicy($response);
        $this->applyStrictTransportSecurity($request, $response);

        return $response;
    }

    private function applyContentSecurityPolicy(Response $response): void
    {
        if (! config('security.headers.csp.enabled', true)) {
            return;
        }

        $header = config('security.headers.csp.report_only', true)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $oppositeHeader = $header === 'Content-Security-Policy'
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->remove($oppositeHeader);
        $response->headers->set($header, $this->contentSecurityPolicy());
    }

    private function contentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://js.stripe.com https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com",
            "img-src 'self' data: https://upload.wikimedia.org https://images.unsplash.com https://unpkg.com https://a.tile.openstreetmap.org https://b.tile.openstreetmap.org https://c.tile.openstreetmap.org",
            "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net https://cdnjs.cloudflare.com",
            "connect-src 'self' https://api.stripe.com https://r.stripe.com https://m.stripe.network",
            "frame-src 'self' about: https://js.stripe.com https://hooks.stripe.com",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }

    private function applyStrictTransportSecurity(Request $request, Response $response): void
    {
        if (
            ! config('security.headers.hsts.enabled', false)
            || ! app()->environment('production')
            || ! $request->isSecure()
        ) {
            $response->headers->remove('Strict-Transport-Security');

            return;
        }

        $maxAge = (int) config('security.headers.hsts.max_age', 31536000);

        $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}");
    }
}
