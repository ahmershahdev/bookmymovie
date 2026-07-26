<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('movies')) {
            return;
        }

        Schema::table('movies', function (Blueprint $table) {
            if (! Schema::hasColumn('movies', 'hero_carousel_enabled')) {
                $table->boolean('hero_carousel_enabled')->default(false)->after('banner_image')->index();
            }

            if (! Schema::hasColumn('movies', 'hero_sort_order')) {
                $table->unsignedSmallInteger('hero_sort_order')->default(0)->after('hero_carousel_enabled');
            }

            if (! Schema::hasColumn('movies', 'hero_eyebrow')) {
                $table->string('hero_eyebrow', 80)->nullable()->after('hero_sort_order');
            }

            if (! Schema::hasColumn('movies', 'hero_tagline')) {
                $table->string('hero_tagline', 220)->nullable()->after('hero_eyebrow');
            }

            if (! Schema::hasColumn('movies', 'hero_image')) {
                $table->string('hero_image')->nullable()->after('hero_tagline');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('movies')) {
            return;
        }

        Schema::table('movies', function (Blueprint $table) {
            foreach (['hero_image', 'hero_tagline', 'hero_eyebrow', 'hero_sort_order', 'hero_carousel_enabled'] as $column) {
                if (Schema::hasColumn('movies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
