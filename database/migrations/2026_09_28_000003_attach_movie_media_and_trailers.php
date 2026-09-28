<?php

use App\Support\MovieMedia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Points every film at its bundled WebP artwork and trailers, and shortens
 * the footer copyright line. Safe on databases that ran an early draft of
 * the previous migration with a single `trailer_url` column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('movies', 'trailer_url')) {
            Schema::table('movies', fn (Blueprint $table) => $table->dropColumn('trailer_url'));
        }

        if (! Schema::hasColumn('movies', 'trailers')) {
            Schema::table('movies', fn (Blueprint $table) => $table->json('trailers')->nullable()->after('hero_image'));
        }

        MovieMedia::attach();

        DB::table('site_settings')
            ->where('key', 'copyright_note')
            ->where('value', 'like', 'Designed and built by Syed Ahmer Shah%')
            ->update(['value' => 'By Syed Ahmer Shah · MIT licence']);

        foreach (['site_settings.public', 'nav.movies.cards.v2', 'home.stats'] as $key) {
            Cache::forget($key);
        }
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->where('key', 'copyright_note')
            ->where('value', 'By Syed Ahmer Shah · MIT licence')
            ->update(['value' => 'Designed and built by Syed Ahmer Shah. Open source under the MIT licence.']);
    }
};
