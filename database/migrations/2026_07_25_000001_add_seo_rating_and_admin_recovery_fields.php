<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_pages')) {
            Schema::table('content_pages', function (Blueprint $table) {
                if (! Schema::hasColumn('content_pages', 'meta_description')) {
                    $table->string('meta_description', 180)->nullable()->after('meta_title');
                }

                if (! Schema::hasColumn('content_pages', 'canonical_path')) {
                    $table->string('canonical_path', 220)->nullable()->after('meta_description');
                }
            });
        }

        if (Schema::hasTable('movies')) {
            Schema::table('movies', function (Blueprint $table) {
                if (! Schema::hasColumn('movies', 'meta_title')) {
                    $table->string('meta_title', 80)->nullable()->after('trailer_url');
                }

                if (! Schema::hasColumn('movies', 'meta_description')) {
                    $table->string('meta_description', 180)->nullable()->after('meta_title');
                }

                if (! Schema::hasColumn('movies', 'rating_mode')) {
                    $table->enum('rating_mode', ['real', 'fake'])->default('real')->after('total_reviews');
                }

                if (! Schema::hasColumn('movies', 'fake_average_rating')) {
                    $table->decimal('fake_average_rating', 3, 2)->nullable()->after('rating_mode');
                }

                if (! Schema::hasColumn('movies', 'fake_total_reviews')) {
                    $table->unsignedInteger('fake_total_reviews')->nullable()->after('fake_average_rating');
                }
            });
        }

        if (Schema::hasTable('site_settings')) {
            $defaults = [
                ['key' => 'site_name', 'value' => 'BookMyMovie', 'type' => 'string', 'is_public' => true],
                ['key' => 'default_meta_title', 'value' => 'BookMyMovie - Book Cinema Tickets Online', 'type' => 'string', 'is_public' => true],
                ['key' => 'default_meta_description', 'value' => 'Book movie tickets, compare shows, reserve seats, and manage cinema bookings online with BookMyMovie.', 'type' => 'string', 'is_public' => true],
                ['key' => 'canonical_base_url', 'value' => 'https://bookmymovie.ahmershah.dev', 'type' => 'string', 'is_public' => true],
                ['key' => 'support_email', 'value' => 'support@bookmymovie.ahmershah.dev', 'type' => 'string', 'is_public' => true],
            ];

            foreach ($defaults as $setting) {
                DB::table('site_settings')->updateOrInsert(
                    ['key' => $setting['key']],
                    [
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                        'is_public' => $setting['is_public'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('movies')) {
            Schema::table('movies', function (Blueprint $table) {
                foreach (['fake_total_reviews', 'fake_average_rating', 'rating_mode', 'meta_description', 'meta_title'] as $column) {
                    if (Schema::hasColumn('movies', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('content_pages')) {
            Schema::table('content_pages', function (Blueprint $table) {
                foreach (['canonical_path', 'meta_description'] as $column) {
                    if (Schema::hasColumn('content_pages', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
