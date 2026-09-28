<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Back-office controls:
 *  - concessions.stock: units left to pre-order (null = not tracked). Checkout
 *    takes stock with a guarded UPDATE, so two buyers can never take the last
 *    popcorn twice; cancellations put it back.
 *  - movies.bookings_enabled: pause or resume sales for one film without
 *    touching its shows or its status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concessions', function (Blueprint $table) {
            $table->unsignedInteger('stock')->nullable()->after('price');
            $table->unsignedInteger('low_stock_at')->default(10)->after('stock');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->boolean('bookings_enabled')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('movies', fn (Blueprint $table) => $table->dropColumn('bookings_enabled'));
        Schema::table('concessions', fn (Blueprint $table) => $table->dropColumn(['stock', 'low_stock_at']));
    }
};
