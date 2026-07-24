<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('movies')) {
            Schema::table('movies', function (Blueprint $table) {
                if (! Schema::hasColumn('movies', 'base_price')) {
                    $table->decimal('base_price', 10, 2)->default(2500)->after('trailer_url');
                }

                if (! Schema::hasColumn('movies', 'sale_price')) {
                    $table->decimal('sale_price', 10, 2)->nullable()->after('base_price');
                }
            });
        }

        if (Schema::hasTable('show_seat_prices')) {
            Schema::table('show_seat_prices', function (Blueprint $table) {
                if (! Schema::hasColumn('show_seat_prices', 'sale_price')) {
                    $table->decimal('sale_price', 8, 2)->nullable()->after('price');
                }

                if (! Schema::hasColumn('show_seat_prices', 'kids_sale_price')) {
                    $table->decimal('kids_sale_price', 8, 2)->nullable()->after('kids_price');
                }
            });
        }

        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 120)->unique();
                $table->text('value')->nullable();
                $table->string('type', 30)->default('string');
                $table->boolean('is_public')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('content_pages')) {
            Schema::create('content_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 120)->unique();
                $table->string('title', 200);
                $table->string('meta_title', 220)->nullable();
                $table->string('hero_label', 120)->nullable();
                $table->text('excerpt')->nullable();
                $table->longText('body')->nullable();
                $table->json('sections')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('faqs')) {
            Schema::table('faqs', function (Blueprint $table) {
                if (! Schema::hasColumn('faqs', 'category')) {
                    $table->string('category', 100)->default('General')->after('id')->index();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
        Schema::dropIfExists('site_settings');

        if (Schema::hasTable('faqs')) {
            Schema::table('faqs', function (Blueprint $table) {
                if (Schema::hasColumn('faqs', 'category')) {
                    $table->dropColumn('category');
                }
            });
        }

        if (Schema::hasTable('show_seat_prices')) {
            Schema::table('show_seat_prices', function (Blueprint $table) {
                if (Schema::hasColumn('show_seat_prices', 'kids_sale_price')) {
                    $table->dropColumn('kids_sale_price');
                }

                if (Schema::hasColumn('show_seat_prices', 'sale_price')) {
                    $table->dropColumn('sale_price');
                }
            });
        }

        if (Schema::hasTable('movies')) {
            Schema::table('movies', function (Blueprint $table) {
                if (Schema::hasColumn('movies', 'sale_price')) {
                    $table->dropColumn('sale_price');
                }

                if (Schema::hasColumn('movies', 'base_price')) {
                    $table->dropColumn('base_price');
                }
            });
        }
    }
};
