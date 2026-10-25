<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * chk_bookings_amounts required total = subtotal - discount, so every checkout
 * with snacks, a gift card or loyalty points was refused by the database.
 * The total is what is still due after all of them (1 point = PKR 1).
 */
return new class extends Migration
{
    private const RULE = 'subtotal >= 0 AND discount_amount >= 0 AND discount_amount <= subtotal'
        .' AND addons_total >= 0 AND gift_card_amount >= 0 AND points_redeemed >= 0 AND total_amount >= 0'
        .' AND ABS(total_amount - (subtotal - discount_amount + addons_total - gift_card_amount - points_redeemed)) < 0.01';

    private const OLD_RULE = 'subtotal >= 0 AND discount_amount >= 0 AND discount_amount <= subtotal AND total_amount = subtotal - discount_amount';

    public function up(): void
    {
        $this->replace(self::RULE);
    }

    public function down(): void
    {
        $this->replace(self::OLD_RULE);
    }

    private function replace(string $rule): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE bookings DROP CONSTRAINT chk_bookings_amounts');
        } catch (\Throwable) {
            // Not created on this server.
        }

        DB::statement("ALTER TABLE bookings ADD CONSTRAINT chk_bookings_amounts CHECK ({$rule})");
    }
};
