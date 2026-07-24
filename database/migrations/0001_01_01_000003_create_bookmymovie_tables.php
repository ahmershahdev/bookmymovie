<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 230)->unique();
            $table->text('description')->nullable();
            $table->string('language', 50)->default('English')->index();
            $table->unsignedSmallInteger('duration_minutes');
            $table->enum('certificate_rating', ['U', 'UA', 'A', 'S', 'G', 'PG', 'PG-13', 'R'])->nullable();
            $table->date('release_date')->index();
            $table->enum('status', ['coming_soon', 'now_showing', 'ended'])->default('coming_soon')->index();
            $table->string('poster_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('trailer_url', 500)->nullable();
            $table->decimal('base_price', 10, 2)->default(2500);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->boolean('kids_discount_eligible')->default(false);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->foreignId('created_by')->constrained('admins');
            $table->timestamps();
            $table->softDeletes()->index();
        });

        Schema::create('movie_genres', function (Blueprint $table) {
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('genre_id')->constrained('genres')->cascadeOnDelete()->cascadeOnUpdate();
            $table->primary(['movie_id', 'genre_id']);
        });

        Schema::create('theaters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('address');
            $table->string('city', 100)->index();
            $table->string('state', 100);
            $table->string('pincode', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->constrained('admins');
            $table->timestamps();
        });

        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theater_id')->constrained('theaters')->cascadeOnDelete();
            $table->string('screen_name', 50);
            $table->unsignedSmallInteger('total_seats');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['theater_id', 'screen_name']);
        });

        Schema::create('seat_categories', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['Gold', 'Platinum', 'Box'])->unique();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained('screens')->cascadeOnDelete();
            $table->foreignId('seat_category_id')->constrained('seat_categories');
            $table->char('row_label', 2);
            $table->unsignedSmallInteger('seat_number');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['screen_id', 'row_label', 'seat_number']);
        });

        Schema::create('shows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies');
            $table->foreignId('screen_id')->constrained('screens');
            $table->date('show_date')->index();
            $table->time('show_time');
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled')->index();
            $table->unsignedSmallInteger('total_seats');
            $table->unsignedSmallInteger('booked_seats')->default(0);
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('admins');
            $table->timestamps();

            $table->unique(['screen_id', 'show_date', 'show_time']);
            $table->index(['movie_id', 'show_date']);
        });

        Schema::create('show_seat_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained('shows')->cascadeOnDelete();
            $table->foreignId('seat_category_id')->constrained('seat_categories');
            $table->decimal('price', 8, 2);
            $table->decimal('sale_price', 8, 2)->nullable();
            $table->decimal('kids_price', 8, 2)->nullable();
            $table->decimal('kids_sale_price', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['show_id', 'seat_category_id']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 8, 2);
            $table->decimal('max_discount_amount', 8, 2)->nullable();
            $table->decimal('min_order_amount', 8, 2)->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedTinyInteger('max_uses_per_user')->default(1);
            $table->dateTime('valid_from')->index();
            $table->dateTime('valid_until');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->constrained('admins');
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('show_id')->constrained('shows')->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained('seats');
            $table->foreignId('seat_category_id')->constrained('seat_categories');
            $table->enum('ticket_type', ['adult', 'kid'])->default('adult');
            $table->decimal('price', 8, 2);
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['seat_id', 'show_id']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 24)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('show_id')->constrained('shows');
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->unsignedTinyInteger('seat_count');
            $table->unsignedTinyInteger('adult_count')->default(0);
            $table->unsignedTinyInteger('kids_count')->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['cod'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending')->index();
            $table->enum('booking_status', ['confirmed', 'cancelled', 'completed', 'no_show'])->default('confirmed')->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->enum('cancelled_by', ['user', 'admin'])->nullable();
            $table->timestamp('booked_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained('seats');
            $table->foreignId('show_id')->constrained('shows');
            $table->foreignId('seat_category_id')->constrained('seat_categories');
            $table->enum('ticket_type', ['adult', 'kid'])->default('adult');
            $table->decimal('price_paid', 8, 2);
            $table->string('ticket_number', 30)->unique();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['seat_id', 'show_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->enum('payment_method', ['cod'])->default('cod');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'refunded', 'failed'])->default('pending')->index();
            $table->string('transaction_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('booking_id')->unique()->constrained('bookings');
            $table->decimal('discount_applied', 8, 2);
            $table->timestamp('used_at')->useCurrent();

            $table->index(['coupon_id', 'user_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review_text')->nullable();
            $table->boolean('is_approved')->default(false)->index();
            $table->boolean('is_flagged')->default(false)->index();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'movie_id']);
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'movie_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100)->index();
            $table->string('title');
            $table->text('message');
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'is_read']);
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('type', 100);
            $table->string('title');
            $table->text('message');
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['admin_id', 'is_read']);
        });

        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins');
            $table->string('action', 100)->index();
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['model_type', 'model_id']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100)->default('General')->index();
            $table->string('question', 500);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->constrained('admins');
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->index();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_read')->default(false)->index();
            $table->boolean('is_replied')->default(false);
            $table->foreignId('replied_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->text('reply_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        $this->createViews();
    }

    public function down(): void
    {
        foreach (['v_user_stats', 'v_coupon_stats', 'v_movie_stats', 'v_seat_availability', 'v_booking_details', 'v_show_details'] as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }

        foreach ([
            'contact_messages',
            'faqs',
            'admin_activity_logs',
            'admin_notifications',
            'notifications',
            'wishlists',
            'reviews',
            'coupon_usages',
            'payments',
            'booking_seats',
            'bookings',
            'cart_items',
            'carts',
            'coupons',
            'show_seat_prices',
            'shows',
            'seats',
            'seat_categories',
            'screens',
            'theaters',
            'movie_genres',
            'movies',
            'genres',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createViews(): void
    {
        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_show_details AS
SELECT
    s.id AS show_id,
    s.show_date,
    s.show_time,
    s.status AS show_status,
    s.total_seats,
    s.booked_seats,
    (s.total_seats - s.booked_seats) AS available_seats,
    m.id AS movie_id,
    m.title AS movie_title,
    m.slug AS movie_slug,
    m.duration_minutes,
    m.language,
    m.certificate_rating,
    m.poster_image,
    m.status AS movie_status,
    m.average_rating,
    m.kids_discount_eligible,
    sc.id AS screen_id,
    sc.screen_name,
    t.id AS theater_id,
    t.name AS theater_name,
    t.city,
    t.state
FROM shows s
JOIN movies m ON s.movie_id = m.id
JOIN screens sc ON s.screen_id = sc.id
JOIN theaters t ON sc.theater_id = t.id
WHERE m.deleted_at IS NULL
  AND sc.is_active = 1
  AND t.is_active = 1
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_booking_details AS
SELECT
    b.id AS booking_id,
    b.booking_number,
    b.booking_status,
    b.payment_status,
    b.payment_method,
    b.seat_count,
    b.adult_count,
    b.kids_count,
    b.subtotal,
    b.discount_amount,
    b.total_amount,
    b.booked_at,
    b.cancelled_at,
    b.cancellation_reason,
    b.cancelled_by,
    u.id AS user_id,
    u.name AS user_name,
    u.email AS user_email,
    u.phone AS user_phone,
    s.show_date,
    s.show_time,
    s.status AS show_status,
    m.id AS movie_id,
    m.title AS movie_title,
    m.poster_image,
    m.duration_minutes,
    sc.screen_name,
    t.id AS theater_id,
    t.name AS theater_name,
    t.city,
    t.address AS theater_address,
    c.code AS coupon_code,
    p.status AS payment_status_detail,
    p.paid_at,
    p.refunded_at
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN shows s ON b.show_id = s.id
JOIN movies m ON s.movie_id = m.id
JOIN screens sc ON s.screen_id = sc.id
JOIN theaters t ON sc.theater_id = t.id
LEFT JOIN coupons c ON b.coupon_id = c.id
LEFT JOIN payments p ON b.id = p.booking_id
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_seat_availability AS
SELECT
    se.id AS seat_id,
    se.screen_id,
    se.row_label,
    se.seat_number,
    cat.id AS seat_category_id,
    cat.name AS category_name,
    sh.id AS show_id,
    ssp.price,
    ssp.sale_price,
    ssp.kids_price,
    ssp.kids_sale_price,
    CASE
        WHEN se.is_active = 0 THEN 'inactive'
        WHEN bs.id IS NOT NULL THEN 'booked'
        WHEN ci.id IS NOT NULL THEN 'reserved'
        ELSE 'available'
    END AS seat_status
FROM seats se
JOIN seat_categories cat ON se.seat_category_id = cat.id
JOIN shows sh ON sh.screen_id = se.screen_id
LEFT JOIN show_seat_prices ssp ON sh.id = ssp.show_id AND se.seat_category_id = ssp.seat_category_id
LEFT JOIN booking_seats bs ON se.id = bs.seat_id AND sh.id = bs.show_id
    AND bs.booking_id IN (SELECT id FROM bookings WHERE booking_status NOT IN ('cancelled'))
LEFT JOIN cart_items ci ON se.id = ci.seat_id AND sh.id = ci.show_id
    AND ci.cart_id IN (SELECT id FROM carts WHERE expires_at > NOW())
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_movie_stats AS
SELECT
    m.id AS movie_id,
    m.title,
    m.status,
    m.release_date,
    m.average_rating,
    m.total_reviews,
    COUNT(DISTINCT sh.id) AS total_shows,
    COUNT(DISTINCT b.id) AS total_bookings,
    COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount END), 0) AS total_revenue,
    COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.seat_count END), 0) AS tickets_sold,
    COUNT(DISTINCT w.user_id) AS wishlist_count
FROM movies m
LEFT JOIN shows sh ON m.id = sh.movie_id AND sh.status != 'cancelled'
LEFT JOIN bookings b ON sh.id = b.show_id
LEFT JOIN wishlists w ON m.id = w.movie_id
WHERE m.deleted_at IS NULL
GROUP BY m.id, m.title, m.status, m.release_date, m.average_rating, m.total_reviews
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_coupon_stats AS
SELECT
    c.id,
    c.code,
    c.discount_type,
    c.discount_value,
    c.max_discount_amount,
    c.min_order_amount,
    c.max_uses,
    c.used_count,
    c.valid_from,
    c.valid_until,
    c.is_active,
    CASE
        WHEN c.is_active = 0 THEN 'inactive'
        WHEN NOW() < c.valid_from THEN 'upcoming'
        WHEN NOW() > c.valid_until THEN 'expired'
        WHEN c.max_uses IS NOT NULL AND c.used_count >= c.max_uses THEN 'exhausted'
        ELSE 'active'
    END AS effective_status,
    COALESCE(SUM(cu.discount_applied), 0) AS total_discount_given,
    COUNT(cu.id) AS total_redemptions,
    COUNT(DISTINCT cu.user_id) AS unique_users
FROM coupons c
LEFT JOIN coupon_usages cu ON c.id = cu.coupon_id
GROUP BY c.id, c.code, c.discount_type, c.discount_value,
         c.max_discount_amount, c.min_order_amount,
         c.max_uses, c.used_count, c.valid_from, c.valid_until, c.is_active
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_user_stats AS
SELECT
    u.id AS user_id,
    u.name,
    u.email,
    u.phone,
    u.is_blocked,
    u.created_at AS registered_at,
    COUNT(DISTINCT b.id) AS total_bookings,
    COALESCE(SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_amount END), 0) AS total_spent,
    COUNT(DISTINCT CASE WHEN b.booking_status = 'cancelled' THEN b.id END) AS cancelled_bookings,
    COUNT(DISTINCT w.id) AS wishlist_items,
    COUNT(DISTINCT r.id) AS reviews_written,
    MAX(b.booked_at) AS last_booking_at
FROM users u
LEFT JOIN bookings b ON u.id = b.user_id
LEFT JOIN wishlists w ON u.id = w.user_id
LEFT JOIN reviews r ON u.id = r.user_id
WHERE u.deleted_at IS NULL
GROUP BY u.id, u.name, u.email, u.phone, u.is_blocked, u.created_at
SQL);
    }
};
