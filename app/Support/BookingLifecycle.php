<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingEvent;
use App\Models\BookingSeat;
use App\Models\Concession;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\LoyaltyTransaction;
use App\Models\Payment;
use App\Models\Show;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Everything that must be undone together when a booking ends early:
 * seats go back on sale, show capacity, coupon use, gift card balance and
 * loyalty points are restored. Customer cancellations and admin refunds
 * share this one path so they can never drift apart.
 *
 * Call inside a transaction that already holds a lock on the booking.
 */
class BookingLifecycle
{
    /** Points earned per rupee paid: 5 points per PKR 100. */
    public const POINTS_PER_RUPEE = 0.05;

    /** Value of one point at checkout, in rupees. */
    public const POINT_VALUE = 1;

    public static function pointsFor(float $amount): int
    {
        return (int) floor(max(0, $amount) * self::POINTS_PER_RUPEE);
    }

    public static function unwind(Booking $booking, string $actor, ?int $actorId, string $reason, bool $refund = false): Booking
    {
        Show::query()->whereKey($booking->show_id)->lockForUpdate()->first();

        $released = BookingSeat::query()->where('booking_id', $booking->id)->update(['seat_lock' => null]);

        Show::query()
            ->whereKey($booking->show_id)
            ->where('booked_seats', '>=', $released)
            ->decrement('booked_seats', $released);

        if ($booking->coupon_id) {
            DB::table('coupon_usages')->where('booking_id', $booking->id)->delete();
            Coupon::query()->whereKey($booking->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
        }

        // Pre-ordered snacks go back on the shelf.
        foreach (DB::table('booking_concessions')->where('booking_id', $booking->id)->get(['concession_id', 'quantity']) as $line) {
            Concession::query()->whereKey($line->concession_id)->whereNotNull('stock')->increment('stock', (int) $line->quantity);
        }

        if ($booking->gift_card_id && (float) $booking->gift_card_amount > 0) {
            GiftCard::query()->whereKey($booking->gift_card_id)->increment('balance', (float) $booking->gift_card_amount);
        }

        self::reversePoints($booking);

        $wasPaid = $booking->payment_status === 'paid';
        $booking->forceFill([
            'booking_status' => 'cancelled',
            'payment_status' => $wasPaid && $refund ? 'refunded' : 'failed',
            'cancelled_at' => now(),
            'cancelled_by' => $actor,
            'cancellation_reason' => Str::limit($reason, 250, ''),
        ])->save();

        Payment::query()->where('booking_id', $booking->id)->update($wasPaid && $refund
            ? ['status' => 'refunded', 'refunded_at' => now(), 'refund_reason' => Str::limit($reason, 250, '')]
            : ['status' => 'failed', 'notes' => 'Booking cancelled before payment.']);

        BookingEvent::create([
            'booking_id' => $booking->id,
            'event' => $refund ? 'refunded' : 'cancelled',
            'from_status' => 'confirmed',
            'to_status' => 'cancelled',
            'note' => Str::limit(ucfirst($actor).': '.$reason, 250, ''),
            'actor_type' => $actor,
            'actor_id' => $actorId,
        ]);

        return $booking;
    }

    /** Credits points for a new booking and records the ledger entry. */
    public static function awardPoints(Booking $booking): void
    {
        $points = self::pointsFor((float) $booking->total_amount + (float) $booking->gift_card_amount);

        if ($points <= 0) {
            return;
        }

        User::query()->whereKey($booking->user_id)->increment('loyalty_points', $points);
        LoyaltyTransaction::create(['user_id' => $booking->user_id, 'booking_id' => $booking->id, 'points' => $points, 'reason' => 'Earned on '.$booking->booking_number]);
        $booking->forceFill(['points_earned' => $points])->save();
    }

    private static function reversePoints(Booking $booking): void
    {
        if ($booking->points_redeemed > 0) {
            User::query()->whereKey($booking->user_id)->increment('loyalty_points', $booking->points_redeemed);
            LoyaltyTransaction::create(['user_id' => $booking->user_id, 'booking_id' => $booking->id, 'points' => $booking->points_redeemed, 'reason' => 'Returned from cancelled '.$booking->booking_number]);
        }

        if ($booking->points_earned > 0) {
            // Never below zero: points already spent elsewhere stay spent.
            $user = User::query()->whereKey($booking->user_id)->lockForUpdate()->first();
            $taken = min($booking->points_earned, (int) $user?->loyalty_points);
            if ($taken > 0) {
                User::query()->whereKey($booking->user_id)->decrement('loyalty_points', $taken);
                LoyaltyTransaction::create(['user_id' => $booking->user_id, 'booking_id' => $booking->id, 'points' => -$taken, 'reason' => 'Reversed for cancelled '.$booking->booking_number]);
            }
        }
    }

    /**
     * Signature printed in the ticket QR code. The box office recomputes it,
     * so a ticket cannot be forged by editing the booking number.
     */
    public static function ticketSignature(string $bookingNumber): string
    {
        return substr(hash_hmac('sha256', 'ticket|'.$bookingNumber, (string) config('app.key')), 0, 16);
    }

    public static function ticketPayload(string $bookingNumber): string
    {
        return route('admin.tickets.verify', ['number' => $bookingNumber, 'signature' => self::ticketSignature($bookingNumber)]);
    }
}
