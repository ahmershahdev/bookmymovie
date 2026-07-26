<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\Movie;
use App\Models\SiteSetting;
use Illuminate\Database\QueryException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('cart-actions', fn (Request $request) => [
            Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()),
        ]);

        RateLimiter::for('wishlist-actions', fn (Request $request) => [
            Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()),
        ]);

        RateLimiter::for('checkout-actions', fn (Request $request) => [
            Limit::perMinute(4)->by($request->user()?->id ?: $request->ip()),
        ]);

        RateLimiter::for('contact-form', fn (Request $request) => [
            Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()),
        ]);

        View::composer('*', function ($view) {
            $view->with('initialWishlistCount', 0);
            $view->with('initialCartCount', 0);

            try {
                $view->with('siteSettings', Schema::hasTable('site_settings') ? SiteSetting::publicMap() : []);

                $view->with('navMovies', Schema::hasTable('movies')
                    ? Movie::query()
                        ->with('genres')
                        ->whereIn('status', ['now_showing', 'coming_soon'])
                        ->limit(8)
                        ->get()
                        ->map(fn (Movie $movie) => $movie->toCardArray())
                        ->all()
                    : []);
            } catch (QueryException) {
                $view->with('siteSettings', []);
                $view->with('navMovies', []);
            }

            try {
                if (Auth::check()) {
                    $cart = Cart::query()
                        ->where('user_id', Auth::id())
                        ->where('expires_at', '>', now())
                        ->first();

                    $view->with('initialWishlistCount', Auth::user()->wishlists()->count());
                    $view->with('initialCartCount', $cart?->items()->count() ?? 0);
                }
            } catch (QueryException) {
                //
            }
        });
    }
}
