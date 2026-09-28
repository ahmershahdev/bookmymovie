<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSplit;
use App\Support\SplitPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/**
 * Split-the-bill. The host manages shares from their e-ticket; friends
 * open a private link (40 random characters) that shows only the film,
 * the time and their own share: never the host's contact details.
 */
class SplitController extends Controller
{
    public function store(Request $request, string $number): RedirectResponse
    {
        $booking = Booking::query()->where('user_id', Auth::id())->where('booking_number', $number)->firstOrFail();
        $data = $request->validate(['people' => ['required', 'integer', 'min:2', 'max:'.min(SplitPayments::MAX_PEOPLE, max(2, (int) $booking->seat_count))]]);

        try {
            SplitPayments::create($booking, (int) $data['people']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['split' => $exception->getMessage() === 'already-paid'
                ? 'Someone has already paid their share, so the split cannot be changed.'
                : 'This booking can no longer be split (it is paid, cancelled or the show has started).']);
        }

        return back()->with('status', 'Bill split '.$data['people'].' ways. Send each friend their link.');
    }

    public function destroy(string $number): RedirectResponse
    {
        $booking = Booking::query()->where('user_id', Auth::id())->where('booking_number', $number)->firstOrFail();

        try {
            SplitPayments::cancel($booking);
        } catch (RuntimeException) {
            return back()->withErrors(['split' => 'Someone has already paid their share, so the split cannot be removed.']);
        }

        return back()->with('status', 'Split removed. The full amount is due from you again.');
    }

    public function show(string $token): Response
    {
        $split = $this->share($token);
        $booking = $split->booking()->with(['show.movie', 'show.screen.theater'])->firstOrFail();
        $startsAt = $booking->showStartsAt();
        $shares = BookingSplit::query()->where('booking_id', $booking->id)->orderByDesc('is_host')->orderBy('id')->get();

        return $this->page('Public/Split', [
            'share' => [
                'label' => $split->label,
                'amount' => (float) $split->amount,
                'status' => $split->status,
                'paid_at' => $split->paid_at?->format('j M, g:i A'),
                'token' => $token,
            ],
            'booking' => [
                'number' => $booking->booking_number,
                'film' => $booking->show->movie->title,
                'movie' => $booking->show->movie->toCardArray(0),
                'when' => $startsAt?->format('l j F, g:i A'),
                'cinema' => $booking->show->screen->theater->name,
                'seats' => (int) $booking->seat_count,
                'host' => strtok((string) ($booking->customer_name ?: 'Your friend'), ' '),
                'cancelled' => $booking->booking_status === 'cancelled',
            ],
            'progress' => [
                'paid' => $shares->where('status', 'paid')->count(),
                'total' => $shares->count(),
            ],
            'cardEnabled' => SplitPayments::cardEnabled(),
        ], ['title' => 'Your share | BookMyMovie', 'description' => 'Pay your share of a group booking.', 'robots' => 'noindex, nofollow']);
    }

    public function pay(Request $request, string $token): HttpResponse
    {
        $split = $this->share($token);
        $request->validate(['name' => ['nullable', 'string', 'max:100']]);
        if ($request->filled('name')) {
            $split->forceFill(['payer_name' => strip_tags((string) $request->input('name'))])->save();
        }

        abort_unless(SplitPayments::cardEnabled(), 404);
        if ($split->status === 'paid' || $split->booking->booking_status !== 'confirmed') {
            return redirect()->route('split.show', $token);
        }

        try {
            return Inertia::location(SplitPayments::startCard($split));
        } catch (Throwable $exception) {
            Log::error('Split payment could not start', ['split' => $split->id, 'error' => $exception->getMessage()]);

            return redirect()->route('split.show', $token)->withErrors(['pay' => 'The card service is not responding. Try again, or pay at the box office.']);
        }
    }

    public function back(string $token): RedirectResponse
    {
        $split = $this->share($token);

        try {
            $split = SplitPayments::refreshCard($split);
        } catch (Throwable $exception) {
            Log::warning('Split payment lookup failed', ['split' => $split->id, 'error' => $exception->getMessage()]);
        }

        return redirect()->route('split.show', $token)->with('status', $split->status === 'paid' ? 'Paid. Thank you, enjoy the film!' : 'The payment was not completed. You can try again or pay at the box office.');
    }

    private function share(string $token): BookingSplit
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40}$/', $token) === 1, 404);

        return BookingSplit::query()->with('booking')->where('token', $token)->firstOrFail();
    }
}
