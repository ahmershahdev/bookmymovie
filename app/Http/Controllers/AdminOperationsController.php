<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingEvent;
use App\Models\GiftCard;
use App\Models\Payment;
use App\Models\User;
use App\Support\AuditLog;
use App\Support\BookingLifecycle;
use App\Support\TransactionalMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * Box-office and back-office work outside the main dashboard: the audit log,
 * refunds, gift cards and checking a ticket's QR code at the door.
 */
class AdminOperationsController extends AdminController
{
    public function activity(Request $request): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            return redirect()->route('admin.login');
        }

        $logs = DB::table('audit_logs')->latest('id')->limit(400)->get();

        $names = [
            'admin' => Admin::query()->whereIn('id', $logs->where('actor_type', 'admin')->pluck('actor_id')->filter()->unique())->pluck('name', 'id'),
            'user' => User::withTrashed()->whereIn('id', $logs->where('actor_type', 'user')->pluck('actor_id')->filter()->unique())->pluck('name', 'id'),
        ];

        $bookings = Booking::query()
            ->with(['show.movie:id,title', 'user:id,name'])
            ->latest('booked_at')
            ->limit(60)
            ->get();

        return $this->page('Admin/Activity', [
            'logs' => $logs->map(fn ($log) => [
                'id' => $log->id,
                'at' => \Illuminate\Support\Carbon::parse($log->created_at)->format('j M Y, H:i:s'),
                'actor' => $log->actor_type,
                'actor_name' => $names[$log->actor_type][$log->actor_id] ?? ($log->actor_type === 'guest' ? 'Guest' : ucfirst($log->actor_type).' #'.$log->actor_id),
                'action' => $log->action,
                'subject' => $log->subject_type ? $log->subject_type.' #'.$log->subject_id : null,
                'changes' => $log->changes ? json_decode($log->changes, true) : null,
                'ip' => $log->ip_address,
            ])->values(),
            'bookings' => $bookings->map(fn (Booking $booking) => [
                'number' => $booking->booking_number,
                'customer' => $booking->customer_name ?: $booking->user?->name,
                'film' => $booking->show?->movie?->title,
                'starts' => $booking->showStartsAt()?->format('D j M, g:i A'),
                'total' => (float) $booking->total_amount,
                'gift_card' => (float) $booking->gift_card_amount,
                'points' => (int) $booking->points_redeemed,
                'method' => $booking->payment_method,
                'payment' => $booking->payment_status,
                'status' => $booking->booking_status,
                'refundable' => $booking->booking_status === 'confirmed',
            ])->values(),
            'giftCards' => GiftCard::query()->latest()->limit(30)->get()->map(fn (GiftCard $card) => [
                'id' => $card->id,
                'code' => $card->code,
                'balance' => (float) $card->balance,
                'initial' => (float) $card->initial_balance,
                'recipient' => $card->recipient_email,
                'expires' => $card->expires_at?->format('j M Y'),
                'active' => $card->usable(),
            ])->values(),
        ], ['title' => 'Activity, refunds and gift cards | Admin', 'description' => 'Audit log, refunds and gift cards.', 'robots' => 'noindex, nofollow']);
    }

    /**
     * Cancels a booking from the back office. Paid bookings are marked
     * refunded; seats, coupon use, gift card balance and points all return.
     */
    public function refund(Request $request, string $number): RedirectResponse
    {
        $admin = $this->admin($request);
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate(['reason' => ['required', 'string', 'min:4', 'max:200']]);

        $booking = DB::transaction(function () use ($number, $data, $admin) {
            $booking = Booking::query()->where('booking_number', $number)->lockForUpdate()->firstOrFail();

            if ($booking->booking_status !== 'confirmed') {
                return null;
            }

            return BookingLifecycle::unwind($booking, 'admin', $admin->id, $data['reason'], refund: true);
        }, 3);

        if (! $booking) {
            return back()->withErrors(['refund' => 'Only confirmed bookings can be refunded or cancelled.']);
        }

        $booking->load('user');
        AuditLog::record('booking.refunded', $booking, ['reason' => $data['reason'], 'amount' => (float) $booking->total_amount, 'status' => $booking->payment_status], $request, 'admin', $admin->id);

        if ($booking->user) {
            TransactionalMailer::bookingRefunded($booking->user, $booking, $data['reason']);
        }

        return back()->with('status', $booking->payment_status === 'refunded'
            ? 'Booking '.$number.' refunded. Seats, gift card balance and points were returned.'
            : 'Booking '.$number.' cancelled. Nothing had been paid, so no refund was due.');
    }

    public function issueGiftCard(Request $request): RedirectResponse
    {
        $admin = $this->admin($request);
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:100', 'max:100000'],
            'recipient_email' => ['nullable', 'email:rfc', 'max:150'],
            'message' => ['nullable', 'string', 'max:200'],
            'months' => ['required', 'integer', 'min:1', 'max:36'],
        ]);

        do {
            $code = 'BMM-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (GiftCard::query()->where('code', $code)->exists());

        $card = GiftCard::create([
            'code' => $code,
            'initial_balance' => $data['amount'],
            'balance' => $data['amount'],
            'recipient_email' => $data['recipient_email'] ? strtolower($data['recipient_email']) : null,
            'message' => $data['message'] ?? null,
            'expires_at' => now()->addMonths((int) $data['months']),
            'is_active' => true,
            'issued_by' => $admin->id,
        ]);

        AuditLog::record('gift_card.issued', $card, ['amount' => $data['amount'], 'recipient' => $card->recipient_email], $request, 'admin', $admin->id);

        if ($card->recipient_email) {
            TransactionalMailer::giftCardIssued($card);
        }

        return back()->with('status', 'Gift card '.$code.' issued for PKR '.number_format($data['amount']).'.');
    }

    public function deactivateGiftCard(Request $request, GiftCard $card): RedirectResponse
    {
        $admin = $this->admin($request);
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $card->forceFill(['is_active' => false])->save();
        AuditLog::record('gift_card.deactivated', $card, [], $request, 'admin', $admin->id);

        return back()->with('status', 'Gift card '.$card->code.' switched off.');
    }

    /** The page a box-office phone opens after scanning a ticket's QR code. */
    public function verifyTicket(Request $request, string $number, string $signature): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            $request->session()->put('admin_intended', $request->fullUrl());

            return redirect()->route('admin.login');
        }

        $genuine = hash_equals(BookingLifecycle::ticketSignature($number), $signature);
        $booking = $genuine
            ? Booking::query()->with(['show.movie', 'show.screen.theater', 'seats.seat', 'events', 'concessions'])->where('booking_number', $number)->first()
            : null;

        $startsAt = $booking?->showStartsAt();
        $admitted = $booking?->events->contains('event', 'admitted') ?? false;

        [$verdict, $tone, $detail] = match (true) {
            ! $genuine => ['Not a genuine ticket', 'signal', 'The QR code signature does not match. Do not admit.'],
            ! $booking => ['Booking not found', 'signal', 'No booking has this number.'],
            $booking->booking_status === 'cancelled' => ['Cancelled', 'signal', 'This booking was cancelled '.$booking->cancelled_at?->format('j M, g:i A').'.'],
            $admitted => ['Already admitted', 'signal', 'This ticket was already scanned in. Check the guest’s ID.'],
            $startsAt && $startsAt->copy()->addMinutes(30)->isPast() => ['Show has ended entry', 'signal', 'Entry closed 30 minutes after the start time.'],
            $booking->payment_status !== 'paid' => ['Payment due', 'volt', 'Collect PKR '.number_format((float) $booking->total_amount).' before admitting.'],
            default => ['Valid ticket', 'mint', 'Paid in full. Admit the guests.'],
        };

        return $this->page('Admin/TicketCheck', [
            'number' => $number,
            'signature' => $signature,
            'verdict' => $verdict,
            'tone' => $tone,
            'detail' => $detail,
            'canAdmit' => $booking && $genuine && $booking->booking_status === 'confirmed' && ! $admitted,
            'booking' => $booking ? [
                'film' => $booking->show->movie->title,
                'customer' => $booking->customer_name,
                'cinema' => $booking->show->screen->theater->name.' · '.$booking->show->screen->screen_name,
                'starts' => $startsAt?->format('D j M, g:i A'),
                'seats' => $booking->seats->map(fn ($seat) => $seat->seat->row_label.$seat->seat->seat_number)->values(),
                'adults' => (int) $booking->adult_count,
                'kids' => (int) $booking->kids_count,
                'total' => (float) $booking->total_amount,
                'paid' => $booking->payment_status === 'paid',
                'addons' => $booking->concessions->map(fn ($item) => $item->pivot->quantity.' × '.$item->name)->values(),
            ] : null,
        ], ['title' => 'Ticket check '.$number, 'description' => 'Box-office ticket check.', 'robots' => 'noindex, nofollow']);
    }

    /** Admits the guests, taking payment at the counter first if it was due. */
    public function admit(Request $request, string $number, string $signature): RedirectResponse
    {
        $admin = $this->admin($request);
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        abort_unless(hash_equals(BookingLifecycle::ticketSignature($number), $signature), 403);

        $result = DB::transaction(function () use ($number, $admin) {
            $booking = Booking::query()->where('booking_number', $number)->lockForUpdate()->firstOrFail();

            if ($booking->booking_status !== 'confirmed' || BookingEvent::query()->where('booking_id', $booking->id)->where('event', 'admitted')->exists()) {
                return null;
            }

            if ($booking->payment_status !== 'paid') {
                $booking->forceFill(['payment_status' => 'paid'])->save();
                Payment::query()->where('booking_id', $booking->id)->update(['status' => 'paid', 'paid_at' => now(), 'notes' => 'Paid at the box office.']);
                BookingEvent::create(['booking_id' => $booking->id, 'event' => 'paid', 'from_status' => 'confirmed', 'to_status' => 'confirmed', 'note' => 'Paid at the box office.', 'actor_type' => 'admin', 'actor_id' => $admin->id]);
            }

            BookingEvent::create(['booking_id' => $booking->id, 'event' => 'admitted', 'from_status' => 'confirmed', 'to_status' => 'confirmed', 'note' => 'Scanned in at the door.', 'actor_type' => 'admin', 'actor_id' => $admin->id]);

            return $booking;
        }, 3);

        if (! $result) {
            return back()->withErrors(['admit' => 'This ticket cannot be admitted (already scanned in or cancelled).']);
        }

        AuditLog::record('ticket.admitted', $result, [], $request, 'admin', $admin->id);

        return back()->with('status', 'Admitted. Enjoy the show.');
    }
}
