<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\Movie;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
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
        View::composer('*', function ($view) {
            $view->with('navMovies', Schema::hasTable('movies')
                ? Movie::query()
                    ->with('genres')
                    ->whereIn('status', ['now_showing', 'coming_soon'])
                    ->limit(8)
                    ->get()
                    ->map(fn (Movie $movie) => $movie->toCardArray())
                    ->all()
                : []);

            if (Auth::check()) {
                $cart = Cart::query()
                    ->where('user_id', Auth::id())
                    ->where('expires_at', '>', now())
                    ->first();

                $view->with('initialWishlistCount', Auth::user()->wishlists()->count());
                $view->with('initialCartCount', $cart?->items()->count() ?? 0);
            }
        });
    }
}
