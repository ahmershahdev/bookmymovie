<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Schema upgrade for the 2026 relaunch.
 *
 *  - 3NF: theaters.city/state (state depended on city, a transitive dependency)
 *    move into a `cities` table referenced by theaters.city_id.
 *  - Cast & crew (`people`, `movie_credits`) and cinema amenities (many-to-many).
 *  - Booking audit trail (`booking_events`) and a security log (`security_events`).
 *  - booking_seats.seat_lock: UNIQUE(show_id, seat_id, seat_lock) still makes a
 *    double sale impossible at the storage layer, while a cancelled seat
 *    (seat_lock = NULL) can be sold again. The old UNIQUE(seat_id, show_id)
 *    blocked re-selling a seat forever after one cancellation.
 *  - bookings.idempotency_key: a double-submitted checkout creates one booking.
 *  - CHECK constraints, a FULLTEXT index, rating triggers and reporting views.
 *  - Removes movies.trailer_url: the product no longer embeds video.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createLookupTables();
        $this->upgradeTheaters();
        $this->upgradeScreens();
        $this->upgradeMovies();
        $this->createCredits();
        $this->upgradeBookings();
        $this->createAuditTables();
        $this->addPerformanceIndexes();

        if ($this->isMySql()) {
            $this->addCheckConstraints();
            $this->createRatingTriggers();
        }

        $this->createViews();
    }

    public function down(): void
    {
        if ($this->isMySql()) {
            foreach (['trg_reviews_ai', 'trg_reviews_au', 'trg_reviews_ad'] as $trigger) {
                DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
            }
        }

        foreach (['v_theater_catalog', 'v_show_occupancy', 'v_daily_revenue'] as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }

        Schema::dropIfExists('security_events');
        Schema::dropIfExists('booking_events');
        Schema::dropIfExists('movie_credits');
        Schema::dropIfExists('people');
        Schema::dropIfExists('theater_amenity');
        Schema::dropIfExists('amenities');

        if ($this->isMySql()) {
            foreach ([
                'reviews' => ['chk_reviews_rating'],
                'movies' => ['chk_movies_duration', 'chk_movies_rating', 'chk_movies_prices'],
                'shows' => ['chk_shows_capacity'],
                'bookings' => ['chk_bookings_seats', 'chk_bookings_amounts'],
                'show_seat_prices' => ['chk_ssp_prices'],
                'show_seat_row_prices' => ['chk_ssrp_prices'],
                'coupons' => ['chk_coupons_value', 'chk_coupons_window', 'chk_coupons_usage'],
            ] as $table => $constraints) {
                foreach ($constraints as $name) {
                    try {
                        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
                    } catch (\Throwable) {
                        // Constraint was never created on this server.
                    }
                }
            }
        }

        Schema::table('carts', fn (Blueprint $table) => $table->dropIndex(['expires_at']));
        Schema::table('shows', fn (Blueprint $table) => $table->dropIndex(['status', 'show_date', 'show_time']));
        // InnoDB silently dropped the FK's implicit index once the composite
        // index could serve it, so give the FK a plain index back first.
        Schema::table('reviews', function (Blueprint $table) {
            $this->ensureIndex($table, 'reviews', 'movie_id');
            $table->dropIndex(['movie_id', 'is_approved', 'created_at']);
        });
        Schema::table('contact_messages', fn (Blueprint $table) => $table->dropIndex(['created_at']));

        Schema::table('bookings', function (Blueprint $table) {
            $this->ensureIndex($table, 'bookings', 'user_id');
            $this->ensureIndex($table, 'bookings', 'show_id');
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['user_id', 'booked_at']);
            $table->dropIndex(['show_id', 'booking_status']);
            $table->dropColumn('idempotency_key');
        });

        // Restoring the old rule fails if a seat was re-sold after a cancellation;
        // that data is only valid under the new schema.
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->unique(['seat_id', 'show_id']);
            $this->ensureIndex($table, 'booking_seats', 'show_id');
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropUnique('booking_seats_live_seat_unique');
            $table->dropIndex(['seat_id']);
            $table->dropColumn('seat_lock');
        });

        Schema::table('movies', function (Blueprint $table) {
            if ($this->isMySql()) {
                $table->dropFullText('movies_search_fulltext');
            }

            $table->dropIndex(['status', 'release_date']);
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->string('trailer_url', 500)->nullable();
            $table->dropColumn(['tagline', 'studio', 'country', 'content_advisory']);
        });

        Schema::table('screens', function (Blueprint $table) {
            $table->dropColumn(['format', 'sound_system', 'is_wheelchair_accessible']);
        });

        Schema::table('theaters', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->index();
            $table->string('state', 100)->nullable();
            $table->dropUnique(['slug']);
        });

        DB::statement('UPDATE theaters t JOIN cities c ON c.id = t.city_id SET t.city = c.name, t.state = c.province');

        $this->restoreLegacyViews();

        Schema::table('theaters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn(['slug', 'description', 'latitude', 'longitude', 'opens_at', 'closes_at']);
        });

        Schema::dropIfExists('cities');
    }

    private function createLookupTables(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 90)->unique();
            $table->string('province', 60);
            $table->string('country', 60)->default('Pakistan');
            $table->string('timezone', 40)->default('Asia/Karachi');
            $table->timestamps();

            $table->unique(['name', 'province']);
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 90)->unique();
            $table->string('description', 255)->nullable();
        });

        Schema::create('theater_amenity', function (Blueprint $table) {
            $table->foreignId('theater_id')->constrained('theaters')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities')->cascadeOnDelete();
            $table->primary(['theater_id', 'amenity_id']);
            $table->index('amenity_id');
        });
    }

    private function upgradeTheaters(): void
    {
        Schema::table('theaters', function (Blueprint $table) {
            $table->string('slug', 170)->nullable()->after('name');
            $table->foreignId('city_id')->nullable()->after('address')->constrained('cities')->restrictOnDelete();
            $table->text('description')->nullable()->after('email');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
        });

        // Backfill: one city row per distinct (city, state) pair.
        foreach (DB::table('theaters')->select('city', 'state')->distinct()->get() as $row) {
            $name = trim((string) $row->city) ?: 'Unknown';
            $province = trim((string) $row->state) ?: 'Unknown';
            $slug = Str::slug($name.'-'.$province);

            DB::table('cities')->insertOrIgnore([
                'name' => $name,
                'slug' => $slug,
                'province' => $province,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('theaters')
                ->where('city', $row->city)
                ->where('state', $row->state)
                ->update(['city_id' => DB::table('cities')->where('slug', $slug)->value('id')]);
        }

        foreach (DB::table('theaters')->get(['id', 'name']) as $theater) {
            DB::table('theaters')->where('id', $theater->id)->update([
                'slug' => Str::slug($theater->name).'-'.$theater->id,
            ]);
        }

        Schema::table('theaters', function (Blueprint $table) {
            $table->unique('slug');
            $table->dropIndex(['city']);
            $table->dropColumn(['city', 'state']);
        });
    }

    private function upgradeScreens(): void
    {
        Schema::table('screens', function (Blueprint $table) {
            $table->enum('format', ['standard', 'imax', 'dolby_cinema', '4dx', 'screenx', 'recliner'])
                ->default('standard')
                ->after('screen_name')
                ->index();
            $table->string('sound_system', 60)->nullable()->after('format');
            $table->boolean('is_wheelchair_accessible')->default(true)->after('sound_system');
        });
    }

    private function upgradeMovies(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('trailer_url');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->string('tagline', 180)->nullable()->after('title');
            $table->string('studio', 120)->nullable()->after('language');
            $table->string('country', 80)->nullable()->after('studio');
            $table->string('content_advisory', 255)->nullable()->after('certificate_rating');
            $table->index(['status', 'release_date']);
        });

        if ($this->isMySql()) {
            DB::statement('ALTER TABLE movies ADD FULLTEXT INDEX movies_search_fulltext (title, tagline, description)');
        }
    }

    private function createCredits(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('known_for', 60)->nullable();
            $table->string('birth_place', 120)->nullable();
            $table->text('biography')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('movie_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->enum('role', ['director', 'writer', 'producer', 'cast', 'composer', 'cinematographer', 'editor']);
            $table->string('character_name', 120)->nullable();
            $table->unsignedSmallInteger('billing_order')->default(0);

            $table->unique(['movie_id', 'person_id', 'role']);
            $table->index(['movie_id', 'role', 'billing_order']);
            $table->index('person_id');
        });
    }

    private function upgradeBookings(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            // 1 while the seat is held by a live booking, NULL once released.
            $table->unsignedTinyInteger('seat_lock')->nullable()->default(1)->after('ticket_number');
        });

        DB::table('booking_seats')
            ->whereIn('booking_id', DB::table('bookings')->select('id')->where('booking_status', 'cancelled'))
            ->update(['seat_lock' => null]);

        Schema::table('booking_seats', function (Blueprint $table) {
            // The seat_id FK needs its own index before the composite unique goes.
            $table->index('seat_id');
            $table->unique(['show_id', 'seat_id', 'seat_lock'], 'booking_seats_live_seat_unique');
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropUnique(['seat_id', 'show_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->char('idempotency_key', 36)->nullable()->after('booking_number');
            $table->unique('idempotency_key');
            $table->index(['user_id', 'booked_at']);
            $table->index(['show_id', 'booking_status']);
        });
    }

    private function createAuditTables(): void
    {
        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('event', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->string('note', 255)->nullable();
            $table->enum('actor_type', ['user', 'admin', 'system'])->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['booking_id', 'created_at']);
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('ip_address', 45);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 8)->nullable();
            $table->string('path', 255)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ip_address', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    private function addPerformanceIndexes(): void
    {
        Schema::table('carts', fn (Blueprint $table) => $table->index('expires_at'));
        Schema::table('shows', fn (Blueprint $table) => $table->index(['status', 'show_date', 'show_time']));
        Schema::table('reviews', fn (Blueprint $table) => $table->index(['movie_id', 'is_approved', 'created_at']));
        Schema::table('contact_messages', fn (Blueprint $table) => $table->index('created_at'));
    }

    private function addCheckConstraints(): void
    {
        $checks = [
            'reviews' => ['chk_reviews_rating' => 'rating BETWEEN 1 AND 5'],
            'movies' => [
                'chk_movies_duration' => 'duration_minutes BETWEEN 1 AND 600',
                'chk_movies_rating' => 'average_rating BETWEEN 0 AND 5',
                'chk_movies_prices' => 'sale_price IS NULL OR sale_price <= base_price',
            ],
            'shows' => ['chk_shows_capacity' => 'booked_seats <= total_seats'],
            'bookings' => [
                'chk_bookings_seats' => 'seat_count >= 1 AND seat_count = adult_count + kids_count',
                'chk_bookings_amounts' => 'subtotal >= 0 AND discount_amount >= 0 AND discount_amount <= subtotal AND total_amount = subtotal - discount_amount',
            ],
            'show_seat_prices' => ['chk_ssp_prices' => 'price >= 0 AND (sale_price IS NULL OR sale_price <= price) AND (kids_sale_price IS NULL OR kids_price IS NULL OR kids_sale_price <= kids_price)'],
            'show_seat_row_prices' => ['chk_ssrp_prices' => 'price >= 0 AND (sale_price IS NULL OR sale_price <= price) AND (kids_sale_price IS NULL OR kids_price IS NULL OR kids_sale_price <= kids_price)'],
            'coupons' => [
                'chk_coupons_value' => "discount_value > 0 AND (discount_type <> 'percentage' OR discount_value <= 100)",
                'chk_coupons_window' => 'valid_until > valid_from',
                'chk_coupons_usage' => 'max_uses IS NULL OR used_count <= max_uses',
            ],
        ];

        foreach ($checks as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                try {
                    DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
                } catch (\Throwable $exception) {
                    // Existing rows that violate a rule must be cleaned up by hand;
                    // the deployment itself should not fail over it.
                    Log::warning("Skipped CHECK constraint {$name}: {$exception->getMessage()}");
                }
            }
        }
    }

    /**
     * Keeps movies.average_rating / total_reviews equal to the approved reviews,
     * whichever code path (admin panel, seeder, SQL console) writes the review.
     */
    private function createRatingTriggers(): void
    {
        $refresh = static fn (string $movieRef) => <<<SQL
UPDATE movies m
SET m.average_rating = COALESCE((SELECT ROUND(AVG(r.rating), 2) FROM reviews r WHERE r.movie_id = {$movieRef} AND r.is_approved = 1), 0),
    m.total_reviews = (SELECT COUNT(*) FROM reviews r WHERE r.movie_id = {$movieRef} AND r.is_approved = 1)
WHERE m.id = {$movieRef}
SQL;

        $triggers = [
            'trg_reviews_ai' => 'AFTER INSERT ON reviews FOR EACH ROW '.$refresh('NEW.movie_id'),
            'trg_reviews_ad' => 'AFTER DELETE ON reviews FOR EACH ROW '.$refresh('OLD.movie_id'),
            'trg_reviews_au' => 'AFTER UPDATE ON reviews FOR EACH ROW BEGIN '.$refresh('NEW.movie_id').'; IF OLD.movie_id <> NEW.movie_id THEN '.$refresh('OLD.movie_id').'; END IF; END',
        ];

        foreach ($triggers as $name => $body) {
            try {
                DB::unprepared("DROP TRIGGER IF EXISTS {$name}");
                DB::unprepared("CREATE TRIGGER {$name} {$body}");
            } catch (\Throwable $exception) {
                // Shared hosts with binary logging may refuse triggers without SUPER.
                // The application recalculates ratings itself, so this is optional.
                Log::warning("Skipped trigger {$name}: {$exception->getMessage()}");
            }
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
    sc.format AS screen_format,
    t.id AS theater_id,
    t.name AS theater_name,
    t.slug AS theater_slug,
    t.address AS theater_address,
    c.id AS city_id,
    c.name AS city,
    c.province AS state
FROM shows s
JOIN movies m ON s.movie_id = m.id
JOIN screens sc ON s.screen_id = sc.id
JOIN theaters t ON sc.theater_id = t.id
JOIN cities c ON t.city_id = c.id
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
    sc.format AS screen_format,
    t.id AS theater_id,
    t.name AS theater_name,
    c.name AS city,
    t.address AS theater_address,
    cp.code AS coupon_code,
    p.status AS payment_status_detail,
    p.paid_at,
    p.refunded_at
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN shows s ON b.show_id = s.id
JOIN movies m ON s.movie_id = m.id
JOIN screens sc ON s.screen_id = sc.id
JOIN theaters t ON sc.theater_id = t.id
JOIN cities c ON t.city_id = c.id
LEFT JOIN coupons cp ON b.coupon_id = cp.id
LEFT JOIN payments p ON b.id = p.booking_id
SQL);

        // A seat is booked while a live booking_seats row holds its lock.
        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_seat_availability AS
SELECT
    se.id AS seat_id,
    se.screen_id,
    se.row_label,
    se.seat_number,
    cat.id AS seat_category_id,
    cat.name AS category_name,
    srp.tier_name AS row_tier_name,
    srp.benefits AS row_benefits,
    COALESCE(srp.price, ssp.price) AS price,
    COALESCE(srp.sale_price, ssp.sale_price) AS sale_price,
    COALESCE(srp.kids_price, ssp.kids_price) AS kids_price,
    COALESCE(srp.kids_sale_price, ssp.kids_sale_price) AS kids_sale_price,
    CASE WHEN srp.id IS NULL THEN 'category' ELSE 'row' END AS pricing_source,
    sh.id AS show_id,
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
LEFT JOIN show_seat_row_prices srp ON sh.id = srp.show_id AND se.row_label = srp.row_label
LEFT JOIN booking_seats bs ON bs.show_id = sh.id AND bs.seat_id = se.id AND bs.seat_lock = 1
LEFT JOIN cart_items ci ON ci.show_id = sh.id AND ci.seat_id = se.id
    AND ci.cart_id IN (SELECT id FROM carts WHERE expires_at > NOW())
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_daily_revenue AS
SELECT
    DATE(b.booked_at) AS sales_date,
    COUNT(*) AS bookings,
    SUM(CASE WHEN b.booking_status <> 'cancelled' THEN b.seat_count ELSE 0 END) AS tickets_sold,
    SUM(CASE WHEN b.booking_status <> 'cancelled' THEN b.total_amount ELSE 0 END) AS gross_revenue,
    SUM(CASE WHEN b.booking_status <> 'cancelled' THEN b.discount_amount ELSE 0 END) AS discounts_given,
    SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END) AS cancellations
FROM bookings b
GROUP BY DATE(b.booked_at)
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_show_occupancy AS
SELECT
    s.id AS show_id,
    s.show_date,
    s.show_time,
    m.title AS movie_title,
    t.name AS theater_name,
    sc.screen_name,
    s.total_seats,
    COUNT(bs.id) AS seats_sold,
    ROUND(COUNT(bs.id) * 100 / NULLIF(s.total_seats, 0), 1) AS occupancy_pct,
    COALESCE(SUM(bs.price_paid), 0) AS gross_ticket_sales
FROM shows s
JOIN movies m ON s.movie_id = m.id
JOIN screens sc ON s.screen_id = sc.id
JOIN theaters t ON sc.theater_id = t.id
LEFT JOIN booking_seats bs ON bs.show_id = s.id AND bs.seat_lock = 1
GROUP BY s.id, s.show_date, s.show_time, m.title, t.name, sc.screen_name, s.total_seats
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_theater_catalog AS
SELECT
    t.id AS theater_id,
    t.name,
    t.slug,
    t.address,
    t.phone,
    t.email,
    t.is_active,
    c.name AS city,
    c.slug AS city_slug,
    c.province,
    (SELECT COUNT(*) FROM screens sc WHERE sc.theater_id = t.id AND sc.is_active = 1) AS screen_count,
    (SELECT COALESCE(SUM(sc.total_seats), 0) FROM screens sc WHERE sc.theater_id = t.id AND sc.is_active = 1) AS seat_capacity,
    (SELECT GROUP_CONCAT(a.name ORDER BY a.name SEPARATOR ', ')
        FROM theater_amenity ta JOIN amenities a ON a.id = ta.amenity_id
        WHERE ta.theater_id = t.id) AS amenity_list
FROM theaters t
JOIN cities c ON t.city_id = c.id
SQL);
    }

    /**
     * Pre-upgrade definitions, so a rollback leaves working views behind.
     */
    private function restoreLegacyViews(): void
    {
        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_show_details AS
SELECT s.id AS show_id, s.show_date, s.show_time, s.status AS show_status, s.total_seats, s.booked_seats,
    (s.total_seats - s.booked_seats) AS available_seats, m.id AS movie_id, m.title AS movie_title,
    m.slug AS movie_slug, m.duration_minutes, m.language, m.certificate_rating, m.poster_image,
    m.status AS movie_status, m.average_rating, m.kids_discount_eligible, sc.id AS screen_id, sc.screen_name,
    t.id AS theater_id, t.name AS theater_name, t.city, t.state
FROM shows s
JOIN movies m ON s.movie_id = m.id
JOIN screens sc ON s.screen_id = sc.id
JOIN theaters t ON sc.theater_id = t.id
WHERE m.deleted_at IS NULL AND sc.is_active = 1 AND t.is_active = 1
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_booking_details AS
SELECT b.id AS booking_id, b.booking_number, b.booking_status, b.payment_status, b.payment_method, b.seat_count,
    b.adult_count, b.kids_count, b.subtotal, b.discount_amount, b.total_amount, b.booked_at, b.cancelled_at,
    b.cancellation_reason, b.cancelled_by, u.id AS user_id, u.name AS user_name, u.email AS user_email,
    u.phone AS user_phone, s.show_date, s.show_time, s.status AS show_status, m.id AS movie_id,
    m.title AS movie_title, m.poster_image, m.duration_minutes, sc.screen_name, t.id AS theater_id,
    t.name AS theater_name, t.city, t.address AS theater_address, c.code AS coupon_code,
    p.status AS payment_status_detail, p.paid_at, p.refunded_at
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
SELECT se.id AS seat_id, se.screen_id, se.row_label, se.seat_number, cat.id AS seat_category_id,
    cat.name AS category_name, srp.tier_name AS row_tier_name, srp.benefits AS row_benefits,
    COALESCE(srp.price, ssp.price) AS price, COALESCE(srp.sale_price, ssp.sale_price) AS sale_price,
    COALESCE(srp.kids_price, ssp.kids_price) AS kids_price,
    COALESCE(srp.kids_sale_price, ssp.kids_sale_price) AS kids_sale_price,
    CASE WHEN srp.id IS NULL THEN 'category' ELSE 'row' END AS pricing_source, sh.id AS show_id,
    CASE WHEN se.is_active = 0 THEN 'inactive' WHEN bs.id IS NOT NULL THEN 'booked'
        WHEN ci.id IS NOT NULL THEN 'reserved' ELSE 'available' END AS seat_status
FROM seats se
JOIN seat_categories cat ON se.seat_category_id = cat.id
JOIN shows sh ON sh.screen_id = se.screen_id
LEFT JOIN show_seat_prices ssp ON sh.id = ssp.show_id AND se.seat_category_id = ssp.seat_category_id
LEFT JOIN show_seat_row_prices srp ON sh.id = srp.show_id AND se.row_label = srp.row_label
LEFT JOIN booking_seats bs ON se.id = bs.seat_id AND sh.id = bs.show_id
    AND bs.booking_id IN (SELECT id FROM bookings WHERE booking_status NOT IN ('cancelled'))
LEFT JOIN cart_items ci ON se.id = ci.seat_id AND sh.id = ci.show_id
    AND ci.cart_id IN (SELECT id FROM carts WHERE expires_at > NOW())
SQL);
    }

    private function ensureIndex(Blueprint $table, string $tableName, string $column): void
    {
        if (! Schema::hasIndex($tableName, "{$tableName}_{$column}_index")) {
            $table->index($column);
        }
    }

    private function isMySql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
