<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('show_seat_row_prices')) {
            Schema::create('show_seat_row_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('show_id')->constrained('shows')->cascadeOnDelete();
                $table->char('row_label', 2);
                $table->string('tier_name', 80);
                $table->string('benefits', 500)->nullable();
                $table->decimal('price', 8, 2);
                $table->decimal('sale_price', 8, 2)->nullable();
                $table->decimal('kids_price', 8, 2)->nullable();
                $table->decimal('kids_sale_price', 8, 2)->nullable();
                $table->timestamps();

                $table->unique(['show_id', 'row_label']);
            });
        }

        $this->replaceSeatAvailabilityView();
        $this->backfillShowtimesAndRowPrices();
    }

    public function down(): void
    {
        Schema::dropIfExists('show_seat_row_prices');
        $this->replaceSeatAvailabilityView(false);
    }

    private function replaceSeatAvailabilityView(bool $withRowPrices = true): void
    {
        $rowSelects = $withRowPrices
            ? "srp.tier_name AS row_tier_name,
    srp.benefits AS row_benefits,
    COALESCE(srp.price, ssp.price) AS price,
    COALESCE(srp.sale_price, ssp.sale_price) AS sale_price,
    COALESCE(srp.kids_price, ssp.kids_price) AS kids_price,
    COALESCE(srp.kids_sale_price, ssp.kids_sale_price) AS kids_sale_price,
    CASE WHEN srp.id IS NULL THEN 'category' ELSE 'row' END AS pricing_source"
            : "cat.name AS row_tier_name,
    cat.description AS row_benefits,
    ssp.price,
    ssp.sale_price,
    ssp.kids_price,
    ssp.kids_sale_price,
    'category' AS pricing_source";

        $rowJoin = $withRowPrices
            ? 'LEFT JOIN show_seat_row_prices srp ON sh.id = srp.show_id AND se.row_label = srp.row_label'
            : '';

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW v_seat_availability AS
SELECT
    se.id AS seat_id,
    se.screen_id,
    se.row_label,
    se.seat_number,
    cat.id AS seat_category_id,
    cat.name AS category_name,
    {$rowSelects},
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
{$rowJoin}
LEFT JOIN booking_seats bs ON se.id = bs.seat_id AND sh.id = bs.show_id
    AND bs.booking_id IN (SELECT id FROM bookings WHERE booking_status NOT IN ('cancelled'))
LEFT JOIN cart_items ci ON se.id = ci.seat_id AND sh.id = ci.show_id
    AND ci.cart_id IN (SELECT id FROM carts WHERE expires_at > NOW())
SQL);
    }

    private function backfillShowtimesAndRowPrices(): void
    {
        if (! Schema::hasTable('movies') || ! Schema::hasTable('shows') || ! Schema::hasTable('screens')) {
            return;
        }

        $adminId = DB::table('admins')->value('id');
        $screenId = DB::table('screens')->where('is_active', true)->value('id') ?: DB::table('screens')->value('id');

        if (! $adminId || ! $screenId) {
            return;
        }

        $times = ['10:00:00', '13:30:00', '17:00:00', '20:30:00'];
        $movies = DB::table('movies')->whereNull('deleted_at')->orderBy('id')->get(['id', 'created_by']);

        foreach ($movies as $movieIndex => $movie) {
            $existingShows = DB::table('shows')
                ->where('movie_id', $movie->id)
                ->where('status', 'scheduled')
                ->whereDate('show_date', '>=', now()->toDateString())
                ->orderBy('show_date')
                ->orderBy('show_time')
                ->get();

            $templateShow = $existingShows->first();
            $movieScreenId = $templateShow->screen_id ?? $screenId;
            $movieAdminId = $movie->created_by ?: $adminId;

            for ($slot = $existingShows->count(); $slot < 4; $slot++) {
                $showTime = $times[$slot % count($times)];
                $dayOffset = intdiv($movieIndex, 4) + $slot;
                $showDate = now()->addDays($dayOffset)->toDateString();

                while (DB::table('shows')->where('screen_id', $movieScreenId)->where('show_date', $showDate)->where('show_time', $showTime)->exists()) {
                    $dayOffset++;
                    $showDate = now()->addDays($dayOffset)->toDateString();
                }

                DB::table('shows')->insert([
                    'movie_id' => $movie->id,
                    'screen_id' => $movieScreenId,
                    'show_date' => $showDate,
                    'show_time' => $showTime,
                    'status' => 'scheduled',
                    'total_seats' => $templateShow->total_seats ?? 60,
                    'booked_seats' => 0,
                    'created_by' => $movieAdminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('shows')->orderBy('id')->get(['id'])->each(function ($show): void {
            foreach ($this->rowPriceRows($show->id) as $row) {
                DB::table('show_seat_row_prices')->updateOrInsert(
                    ['show_id' => $row['show_id'], 'row_label' => $row['row_label']],
                    $row
                );
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowPriceRows(int $showId): array
    {
        $timestamp = now();

        return [
            ['show_id' => $showId, 'row_label' => 'A', 'tier_name' => 'A Front Premium', 'benefits' => 'Closest screen view, recliner pitch, priority entry lane, complimentary drink upgrade', 'price' => 3800, 'sale_price' => 3299, 'kids_price' => 3000, 'kids_sale_price' => 2599, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['show_id' => $showId, 'row_label' => 'B', 'tier_name' => 'B Premium', 'benefits' => 'Front-center view, extra legroom, faster counter support', 'price' => 3400, 'sale_price' => 2899, 'kids_price' => 2700, 'kids_sale_price' => 2299, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['show_id' => $showId, 'row_label' => 'C', 'tier_name' => 'C Prime', 'benefits' => 'Balanced screen distance, central sound coverage, standard comfort seating', 'price' => 3000, 'sale_price' => 2499, 'kids_price' => 2350, 'kids_sale_price' => 1999, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['show_id' => $showId, 'row_label' => 'D', 'tier_name' => 'D Comfort', 'benefits' => 'Mid-hall view, easy aisle access, family-friendly pricing', 'price' => 2700, 'sale_price' => 2199, 'kids_price' => 2100, 'kids_sale_price' => 1749, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['show_id' => $showId, 'row_label' => 'E', 'tier_name' => 'E Saver', 'benefits' => 'Value seating, clear sightline, quick exit access', 'price' => 2400, 'sale_price' => 1999, 'kids_price' => 1900, 'kids_sale_price' => 1599, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['show_id' => $showId, 'row_label' => 'F', 'tier_name' => 'F Back Value', 'benefits' => 'Lowest row price, relaxed rear view, good for groups', 'price' => 2100, 'sale_price' => 1749, 'kids_price' => null, 'kids_sale_price' => null, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ];
    }
};
