<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Engagement and group features:
 *  - push_subscriptions: one row per browser that allowed notifications.
 *  - show_waitlists: members waiting for seats on a sold-out show (FIFO).
 *  - review_photos: up to three re-encoded photos per review.
 *  - booking_splits: a group booking's shares, each paid by one friend via
 *    an unguessable link; the booking is paid when every share is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('endpoint_hash', 64)->unique();
            $table->text('endpoint');
            $table->string('p256dh', 255);
            $table->string('auth', 255);
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('show_waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('seats_wanted')->default(1);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['show_id', 'user_id']);
            $table->index(['show_id', 'notified_at', 'created_at']);
        });

        Schema::create('review_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('booking_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->char('token', 40)->unique();
            $table->string('label', 60);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->boolean('is_host')->default(false);
            $table->string('payer_name', 100)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status']);
        });

        Schema::table('movies', function (Blueprint $table) {
            // So "now open for booking" is announced once per film, not on every edit.
            $table->timestamp('booking_opened_notified_at')->nullable()->after('bookings_enabled');
        });

        // Films already on sale were announced long ago.
        \Illuminate\Support\Facades\DB::table('movies')->where('status', 'now_showing')->update(['booking_opened_notified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('movies', fn (Blueprint $table) => $table->dropColumn('booking_opened_notified_at'));
        Schema::dropIfExists('booking_splits');
        Schema::dropIfExists('review_photos');
        Schema::dropIfExists('show_waitlists');
        Schema::dropIfExists('push_subscriptions');
    }
};
