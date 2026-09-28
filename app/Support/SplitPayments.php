<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingEvent;
use App\Models\BookingSplit;
use App\Models\Payment;
use App\Payments\PaymentGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\StripeClient;

/**
 * Group bookings: the host splits the amount due into equal shares and
 * sends each friend a private link. A share is paid online by card (the
 * Stripe session is looked up server-side, never trusted from the browser)
 * or at the box office. The booking becomes paid when every share is.
 */
class SplitPayments
{
    public const MAX_PEOPLE = 10;

    public static function splittable(Booking $booking): bool
    {
        $startsAt = $booking->showStartsAt();

        return $booking->booking_status === 'confirmed'
            && $booking->payment_status !== 'paid'
            && (float) $booking->total_amount > 0
            && $startsAt instanceof Carbon && $startsAt->isFuture();
    }

    /** Replaces any unpaid shares with `people` equal ones (the host's absorbs rounding). */
    public static function create(Booking $booking, int $people): void
    {
        DB::transaction(function () use ($booking, $people) {
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if (! self::splittable($locked)) {
                throw new RuntimeException('not-splittable');
            }
            if (BookingSplit::query()->where('booking_id', $locked->id)->where('status', 'paid')->exists()) {
                throw new RuntimeException('already-paid');
            }

            BookingSplit::query()->where('booking_id', $locked->id)->delete();

            $cents = (int) round((float) $locked->total_amount * 100);
            $each = intdiv($cents, $people);
            for ($index = 0; $index < $people; $index++) {
                $amount = $index === 0 ? $cents - $each * ($people - 1) : $each;
                BookingSplit::query()->create([
                    'booking_id' => $locked->id,
                    'token' => Str::random(40),
                    'label' => $index === 0 ? 'Host' : 'Friend '.$index,
                    'amount' => $amount / 100,
                    'is_host' => $index === 0,
                    'status' => 'pending',
                ]);
            }

            BookingEvent::create(['booking_id' => $locked->id, 'event' => 'split', 'note' => 'Bill split '.$people.' ways.', 'actor_type' => 'user', 'actor_id' => $locked->user_id]);
        }, 3);
    }

    public static function cancel(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if (BookingSplit::query()->where('booking_id', $booking->id)->where('status', 'paid')->exists()) {
                throw new RuntimeException('already-paid');
            }
            BookingSplit::query()->where('booking_id', $booking->id)->delete();
        }, 3);
    }

    /**
     * Marks one share paid (idempotent) and settles the booking when it was
     * the last one. Locks booking then share, like every other payment path.
     */
    public static function markPaid(BookingSplit $split, string $method, ?string $reference = null, ?string $payer = null): BookingSplit
    {
        $settledBooking = DB::transaction(function () use ($split, $method, $reference, $payer) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($split->booking_id);
            $share = BookingSplit::query()->lockForUpdate()->findOrFail($split->id);
            if ($share->status === 'paid') {
                return null;
            }

            $share->forceFill([
                'status' => 'paid', 'paid_at' => now(), 'payment_method' => $method,
                'payment_reference' => $reference ? mb_substr($reference, 0, 100) : null,
                'payer_name' => $payer ? mb_substr($payer, 0, 100) : $share->payer_name,
            ])->save();

            $pending = BookingSplit::query()->where('booking_id', $booking->id)->where('status', 'pending')->exists();
            if ($pending || $booking->payment_status === 'paid') {
                return null;
            }

            $booking->forceFill(['payment_status' => 'paid'])->save();
            Payment::query()->where('booking_id', $booking->id)->update(['status' => 'paid', 'paid_at' => now(), 'notes' => 'Paid in shares by the group.']);
            BookingEvent::create(['booking_id' => $booking->id, 'event' => 'paid', 'note' => 'Every share of the split is paid.', 'actor_type' => 'system']);

            return $booking;
        }, 3);

        AuditLog::record('payment.split_share_paid', $split->booking, ['share' => $split->id, 'method' => $method], null, 'system');
        if ($settledBooking) {
            $settledBooking->loadMissing('user');
            TransactionalMailer::paymentReceived($settledBooking->user, $settledBooking);
        }

        return $split->refresh();
    }

    public static function cardEnabled(): bool
    {
        return PaymentGateway::enabled('card');
    }

    /** Starts a Stripe Checkout session for exactly this share's amount. */
    public static function startCard(BookingSplit $split): string
    {
        $booking = $split->booking;
        $session = (new StripeClient((string) config('payments.stripe.secret')))->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => 'split-'.$split->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'pkr',
                    'unit_amount' => (int) round((float) $split->amount * 100),
                    'product_data' => ['name' => 'Share of BookMyMovie booking '.$booking->booking_number],
                ],
            ]],
            'success_url' => route('split.return', $split->token),
            'cancel_url' => route('split.show', $split->token),
            'expires_at' => now()->addMinutes(30)->timestamp,
        ], ['idempotency_key' => 'bmm-split-'.$split->id.'-'.now()->format('YmdHi')]);

        $split->forceFill(['payment_reference' => $session->id])->save();

        return (string) $session->url;
    }

    /** Back from Stripe: verify with the secret key, and the amount, before trusting it. */
    public static function refreshCard(BookingSplit $split): BookingSplit
    {
        if ($split->status === 'paid' || ! self::cardEnabled() || ! str_starts_with((string) $split->payment_reference, 'cs_')) {
            return $split;
        }

        $session = (new StripeClient((string) config('payments.stripe.secret')))->checkout->sessions->retrieve((string) $split->payment_reference);
        if ($session->payment_status === 'paid' && (int) $session->amount_total === (int) round((float) $split->amount * 100)) {
            return self::markPaid($split, 'card', (string) $session->payment_intent);
        }

        return $split;
    }
}
