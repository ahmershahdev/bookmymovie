<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = bin2hex(random_bytes(16));
        app()->instance('csp-nonce', $nonce);
        Vite::useCspNonce($nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->policy($nonce));
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->remove('X-Powered-By');

        // Personal pages must never be stored by shared or browser caches.
        if ($request->user() || ($request->hasSession() && $request->session()->has('admin_id'))) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        // The Vite dev server serves scripts and HMR from another origin locally.
        $dev = app()->isLocal() && Vite::isRunningHot()
            ? ' '.rtrim((string) file_get_contents(public_path('hot'))).' ws: wss:'
            : '';

        return implode('; ', array_filter([
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self' https://accounts.google.com https://www.facebook.com ".implode(' ', (array) config('payments.form_hosts', [])),
            // React renders without eval; only nonce-tagged inline scripts run.
            "script-src 'self' 'nonce-{$nonce}' https://www.google.com https://www.gstatic.com{$dev}",
            "style-src 'self' 'nonce-{$nonce}'{$dev}",
            "style-src-attr 'unsafe-inline'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "connect-src 'self' https://www.google.com{$dev}",
            'frame-src https://www.google.com https://recaptcha.google.com',
            "manifest-src 'self'",
            "worker-src 'none'",
            app()->isLocal() ? null : 'upgrade-insecure-requests',
        ]));
    }
}
