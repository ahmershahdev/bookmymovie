<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * English or Urdu. A signed-in customer's saved choice wins, then the choice
 * made this visit (session), then the long-lived cookie set by the switcher.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'ur'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?: ($request->hasSession() ? $request->session()->get('locale') : null)
            ?: $request->cookie('bmm_locale');

        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'en');

        return $next($request);
    }
}
