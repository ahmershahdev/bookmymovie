<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Default breadcrumb trail per route. Views can pass their own $breadcrumbs
 * (movie, cinema and genre pages do) and the same list feeds both the visible
 * trail and the BreadcrumbList JSON-LD.
 */
class Breadcrumbs
{
    private const SECTIONS = [
        'movies.index' => [['Films', null]],
        'movies.compare' => [['Films', 'movies.index'], ['Compare', null]],
        'search' => [['Search', null]],
        'cinemas.index' => [['Cinemas', null]],
        'offers' => [['Offers', null]],
        'about' => [['About', null]],
        'contact' => [['Help', 'faq'], ['Contact', null]],
        'faq' => [['Help', null]],
        'eticket.info' => [['Help', 'faq'], ['E-tickets', null]],
        'terms' => [['Legal', null], ['Terms of Service', null]],
        'privacy' => [['Legal', null], ['Privacy Policy', null]],
        'refund' => [['Legal', null], ['Cancellations & Refunds', null]],
        'cookies' => [['Legal', null], ['Cookie Policy', null]],
        'accessibility' => [['Accessibility', null]],
        'user.login' => [['Sign in', null]],
        'user.register' => [['Create account', null]],
        'user.verify.notice' => [['Verify email', null]],
        'password.request' => [['Sign in', 'user.login'], ['Forgot password', null]],
        'password.reset' => [['Sign in', 'user.login'], ['Reset password', null]],
        'user.dashboard' => [['Account', null]],
        'user.bookings' => [['Account', 'user.dashboard'], ['Bookings', null]],
        'user.wishlist' => [['Account', 'user.dashboard'], ['Watchlist', null]],
        'user.profile' => [['Account', 'user.dashboard'], ['Profile', null]],
        'user.cart' => [['Cart', null]],
        'user.checkout' => [['Cart', 'user.cart'], ['Checkout', null]],
        'admin.login' => [['Admin', null], ['Sign in', null]],
        'admin.register' => [['Admin', null], ['Register', null]],
        'admin.dashboard' => [['Admin', null], ['Dashboard', null]],
    ];

    /**
     * @return list<array{label: string, url: ?string}>
     */
    public static function for(Request $request): array
    {
        $route = $request->route()?->getName();
        $items = [['label' => 'Home', 'url' => route('home')]];

        if ($route === 'home') {
            return [];
        }

        if (isset(self::SECTIONS[$route])) {
            foreach (self::SECTIONS[$route] as [$label, $target]) {
                $items[] = ['label' => $label, 'url' => $target ? route($target) : null];
            }

            return $items;
        }

        $items[] = ['label' => Str::of($request->segments() ? last($request->segments()) : 'Page')->replace('-', ' ')->headline()->toString(), 'url' => null];

        return $items;
    }
}
