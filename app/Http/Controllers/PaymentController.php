<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Payments\PaymentGateway;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/**
 * Hands the customer to JazzCash, Easypaisa or Stripe and takes them back.
 * Callback endpoints are reached by the provider (often a cross-site POST
 * without our session cookie), so they identify the booking by reference
 * and trust only verified provider data, never the session.
 */
class PaymentController extends Controller
{
    public function start(Request $request, string $number): HttpResponse
    {
        // Providers need a real page load (form POST or off-site redirect).
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('payments.start', $number));
        }

        $booking = Booking::query()->with('payment')->where('user_id', Auth::id())->where('booking_number', $number)->firstOrFail();

        if ($booking->payment_status === 'paid' || $booking->booking_status === 'cancelled' || $booking->payment_method === 'cod') {
            return redirect()->route('user.booking.show', $number);
        }

        abort_unless(PaymentGateway::enabled($booking->payment_method), 404);

        try {
            $next = PaymentGateway::start($booking);
        } catch (Throwable $exception) {
            Log::error('Payment could not start', ['booking' => $number, 'error' => $exception->getMessage()]);

            return redirect()->route('user.booking.show', $number)
                ->withErrors(['booking' => 'We could not reach the payment provider. Your seats are still booked: pay at the counter or try again.']);
        }

        AuditLog::record('payment.started', $booking, ['method' => $booking->payment_method]);

        if ($next['type'] === 'redirect') {
            return Inertia::location($next['url']);
        }

        return $this->handoff($booking->payment_method, $next);
    }

    /** JazzCash posts the signed result back here. */
    public function jazzcash(Request $request): RedirectResponse
    {
        $booking = PaymentGateway::handleJazzcash($request->all());

        return $booking
            ? redirect()->route('payments.return', $booking->booking_number)
            : redirect()->route('home')->with('status', 'We could not verify that payment. If money left your account, contact support with your booking number.');
    }

    /** Easypaisa's first leg returns an auth token that must be confirmed. */
    public function easypaisaConfirm(Request $request, string $number): HttpResponse
    {
        $booking = Booking::query()->where('booking_number', $number)->firstOrFail();
        $token = (string) $request->input('auth_token', '');

        if ($token === '') {
            return redirect()->route('payments.return', $number);
        }

        return $this->handoff('easypaisa', PaymentGateway::easypaisaConfirmForm($booking, $token));
    }

    /** Server-to-server notification from Easypaisa. */
    public function easypaisaIpn(Request $request): HttpResponse
    {
        $booking = PaymentGateway::handleEasypaisaIpn((string) $request->query('url', ''));

        return response()->json(['received' => (bool) $booking]);
    }

    /** Where every provider sends the customer back. */
    public function back(string $number): RedirectResponse
    {
        $booking = Booking::query()->with('payment')->where('booking_number', $number)->firstOrFail();

        if ($booking->payment_method === 'card' && $booking->payment) {
            try {
                $booking = PaymentGateway::refreshStripe($booking->payment) ?? $booking;
            } catch (Throwable $exception) {
                Log::warning('Stripe lookup failed', ['booking' => $number, 'error' => $exception->getMessage()]);
            }
        }

        $message = $booking->payment_status === 'paid'
            ? 'Payment received. You are all set, see you at the movies.'
            : 'Payment was not completed. Your seats are still booked: pay at the counter or try again from your ticket.';

        return redirect()->route('user.booking.show', $number)->with('status', $message);
    }

    /**
     * A tiny page that posts the signed form to the provider. Rendered as a
     * full document (not Inertia) so the browser performs a real POST.
     *
     * @param  array{type: 'form', action: string, fields: array<string, string>}  $form
     */
    private function handoff(string $method, array $form): HttpResponse
    {
        return response()->view('payments.handoff', [
            'provider' => PaymentGateway::METHODS[$method]['label'] ?? 'the payment provider',
            'form' => $form,
        ]);
    }
}
