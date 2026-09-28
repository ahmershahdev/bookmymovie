<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\AuditLog;
use App\Support\WalletPass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * "Add to Apple Wallet" and "Save to Google Wallet" for a customer's own
 * e-ticket. Both carry the same signed QR code as the ticket page.
 */
class TicketController extends Controller
{
    public function apple(string $number): Response
    {
        abort_unless(WalletPass::appleEnabled(), 404);
        $booking = $this->booking($number);

        try {
            $pass = WalletPass::apple($booking);
        } catch (Throwable $exception) {
            Log::error('Apple Wallet pass failed', ['booking' => $number, 'error' => $exception->getMessage()]);

            return back()->withErrors(['booking' => 'The Apple Wallet pass could not be created right now. Your e-ticket still works at the door.']);
        }

        AuditLog::record('ticket.wallet_apple', $booking);

        return response($pass, 200, [
            'Content-Type' => 'application/vnd.apple.pkpass',
            'Content-Disposition' => 'attachment; filename="'.$booking->booking_number.'.pkpass"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function google(string $number): RedirectResponse
    {
        abort_unless(WalletPass::googleEnabled(), 404);
        $booking = $this->booking($number);

        try {
            $url = WalletPass::googleSaveUrl($booking);
        } catch (Throwable $exception) {
            Log::error('Google Wallet pass failed', ['booking' => $number, 'error' => $exception->getMessage()]);

            return back()->withErrors(['booking' => 'The Google Wallet pass could not be created right now. Your e-ticket still works at the door.']);
        }

        AuditLog::record('ticket.wallet_google', $booking);

        return redirect()->away($url);
    }

    private function booking(string $number): Booking
    {
        $booking = Booking::query()
            ->with(['show.movie', 'show.screen.theater', 'seats.seat'])
            ->where('user_id', Auth::id())
            ->where('booking_number', $number)
            ->firstOrFail();

        abort_if($booking->booking_status === 'cancelled', 410, 'This booking was cancelled.');

        return $booking;
    }
}
