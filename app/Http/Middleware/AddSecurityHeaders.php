<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = bin2hex(random_bytes(16));
        app()->instance('csp-nonce', $nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->policy($nonce));
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net",
            "style-src-attr 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "connect-src 'self' https://www.google.com https://www.gstatic.com",
            "frame-src https://www.google.com https://www.youtube.com",
            "upgrade-insecure-requests",
        ]);
    }
}
