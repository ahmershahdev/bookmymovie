<?php

use App\Models\Movie;
use App\Support\MovieMedia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Links the new Brass Monkeys trailer (public/videos/trailers/brass-monkeys-1.*)
 * and moves the film up the home carousel, then clears the cached rails.
 */
return new class extends Migration
{
    public function up(): void
    {
        MovieMedia::attach();

        foreach (Movie::LISTING_CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    public function down(): void
    {
        // Media links are data, not schema; nothing to undo.
    }
};
