<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Food and drink add-ons, loyalty points, gift cards, email two-step
 * sign-in, a preferred language per customer and film trailers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concessions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 80);
            $table->string('name_ur', 80)->nullable();
            $table->string('description', 160)->nullable();
            $table->string('category', 20)->index(); // snack, drink, combo
            $table->decimal('price', 10, 2);
            $table->string('icon', 20)->default('popcorn');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('booking_concessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('concession_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
            $table->unique(['booking_id', 'concession_id']);
        });

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->decimal('initial_balance', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->string('recipient_email', 150)->nullable();
            $table->string('message', 200)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('issued_by')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points'); // positive earns, negative spends
            $table->string('reason', 120);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('addons_total', 10, 2)->default(0)->after('discount_amount');
            $table->foreignId('gift_card_id')->nullable()->after('coupon_id')->constrained('gift_cards')->nullOnDelete();
            $table->decimal('gift_card_amount', 10, 2)->default(0)->after('addons_total');
            $table->unsignedInteger('points_redeemed')->default(0)->after('gift_card_amount');
            $table->unsignedInteger('points_earned')->default(0)->after('points_redeemed');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('locale', 5)->default('en');
        });

        Schema::table('movies', function (Blueprint $table) {
            // [{"src": "videos/trailers/kestrel-1.mp4", "label": "Official trailer"}, …]
            $table->json('trailers')->nullable()->after('hero_image');
        });

        $now = now();
        DB::table('concessions')->insert(array_map(fn (array $item, int $index) => [...$item, 'sort_order' => $index, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now], [
            ['slug' => 'salted-popcorn', 'name' => 'Salted popcorn', 'name_ur' => 'نمکین پاپ کارن', 'description' => 'Large tub, popped fresh every show.', 'category' => 'snack', 'price' => 650, 'icon' => 'popcorn'],
            ['slug' => 'caramel-popcorn', 'name' => 'Caramel popcorn', 'name_ur' => 'کیریمل پاپ کارن', 'description' => 'Large tub, glazed in house.', 'category' => 'snack', 'price' => 790, 'icon' => 'popcorn'],
            ['slug' => 'loaded-nachos', 'name' => 'Loaded nachos', 'name_ur' => 'لوڈڈ ناچوز', 'description' => 'Cheese sauce, jalapeños and salsa.', 'category' => 'snack', 'price' => 890, 'icon' => 'nachos'],
            ['slug' => 'soft-drink', 'name' => 'Soft drink', 'name_ur' => 'سافٹ ڈرنک', 'description' => '750 ml, free refill before the show.', 'category' => 'drink', 'price' => 350, 'icon' => 'drink'],
            ['slug' => 'mineral-water', 'name' => 'Mineral water', 'name_ur' => 'منرل واٹر', 'description' => '500 ml bottle.', 'category' => 'drink', 'price' => 150, 'icon' => 'water'],
            ['slug' => 'cold-coffee', 'name' => 'Cold coffee', 'name_ur' => 'کولڈ کافی', 'description' => 'Iced and blended with vanilla.', 'category' => 'drink', 'price' => 520, 'icon' => 'coffee'],
            ['slug' => 'duo-combo', 'name' => 'Date night combo', 'name_ur' => 'ڈیٹ نائٹ کومبو', 'description' => 'Large popcorn and two soft drinks. Save PKR 200.', 'category' => 'combo', 'price' => 1150, 'icon' => 'combo'],
            ['slug' => 'family-combo', 'name' => 'Family combo', 'name_ur' => 'فیملی کومبو', 'description' => 'Two popcorns, nachos and four drinks. Save PKR 600.', 'category' => 'combo', 'price' => 2890, 'icon' => 'combo'],
        ], array_keys(array_fill(0, 8, null))));
    }

    public function down(): void
    {
        Schema::table('movies', fn (Blueprint $table) => $table->dropColumn('trailers'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['loyalty_points', 'two_factor_enabled', 'locale']));
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_card_id');
            $table->dropColumn(['addons_total', 'gift_card_amount', 'points_redeemed', 'points_earned']);
        });
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('booking_concessions');
        Schema::dropIfExists('concessions');
    }
};
