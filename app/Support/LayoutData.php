<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\SiteSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data every page shell needs: public settings, the search palette's films,
 * footer links and the signed-in customer's counters. Cached where it is
 * shared by everyone, and memoised so one request queries it once.
 */
class LayoutData
{
    /** @var array<string, mixed> */
    private static array $memo = [];

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        return self::remember('settings', fn () => self::ready()
            ? Cache::remember('site_settings.public', now()->addMinutes(10), fn () => SiteSetting::publicMap())
            : []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function navMovies(): array
    {
        return self::remember('navMovies', fn () => self::ready()
            ? Cache::flexible('nav.movies.cards.v2', [600, 1800], fn () => Movie::query()
                ->withCardMetrics()
                ->with('genres')
                ->where('status', 'now_showing')
                ->orderByDesc('average_rating')
                ->limit(8)
                ->get()
                ->map(fn (Movie $movie) => $movie->toCardArray())
                ->all())
            : []);
    }

    /**
     * @return list<array{name: string, slug: string}>
     */
    public static function footerGenres(): array
    {
        return self::remember('footerGenres', fn () => self::ready()
            ? Cache::flexible('footer.genres', [3600, 7200], fn () => Genre::query()
                ->whereHas('movies', fn ($query) => $query->publiclyListed())
                ->orderBy('name')
                ->get(['name', 'slug'])
                ->toArray())
            : []);
    }

    /**
     * @return list<array{name: string, slug: string, city: string}>
     */
    public static function footerCinemas(): array
    {
        return self::remember('footerCinemas', fn () => self::ready()
            ? Cache::flexible('footer.cinemas', [3600, 7200], fn () => DB::table('v_theater_catalog')
                ->where('is_active', true)
                ->orderBy('city')
                ->orderBy('name')
                ->get(['name', 'slug', 'city'])
                ->map(fn ($row) => (array) $row)
                ->all())
            : []);
    }

    /**
     * @return array{cart: int, wishlist: int, holdExpiresAt: ?string}
     */
    public static function counts(): array
    {
        return self::remember('counts', function () {
            if (! Auth::check()) {
                return ['cart' => 0, 'wishlist' => 0, 'holdExpiresAt' => null];
            }

            try {
                $cart = Cart::query()->where('user_id', Auth::id())->where('expires_at', '>', now())->withCount('items')->first();

                return [
                    'wishlist' => Auth::user()->wishlists()->count(),
                    'cart' => (int) ($cart?->items_count ?? 0),
                    // Drives the live seat-hold countdown in the navbar.
                    'holdExpiresAt' => $cart && $cart->items_count ? $cart->expires_at?->toIso8601String() : null,
                ];
            } catch (QueryException) {
                return ['cart' => 0, 'wishlist' => 0, 'holdExpiresAt' => null];
            }
        });
    }

    private static function ready(): bool
    {
        return self::remember('ready', function () {
            try {
                return Schema::hasTable('site_settings');
            } catch (QueryException) {
                return false;
            }
        });
    }

    /** Forgets this request's memoised values, after settings change. */
    public static function flush(): void
    {
        self::$memo = [];
    }

    private static function remember(string $key, \Closure $callback): mixed
    {
        if (! array_key_exists($key, self::$memo)) {
            try {
                self::$memo[$key] = $callback();
            } catch (QueryException) {
                // The database may not be migrated yet; pages still render.
                self::$memo[$key] = [];
            }
        }

        return self::$memo[$key];
    }
}
