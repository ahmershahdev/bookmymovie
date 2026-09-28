<?php

namespace App\Http\Controllers;

use App\Models\Show;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Browser push subscriptions and show waitlists. */
class EngagementController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:1000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        // Only real push services, so the server never posts to arbitrary hosts.
        $host = strtolower((string) parse_url($data['endpoint'], PHP_URL_HOST));
        $allowed = ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com', 'wns2-', 'notify.windows.com'];
        abort_unless(collect($allowed)->contains(fn ($suffix) => str_ends_with($host, $suffix) || str_contains($host, $suffix)), 422, 'Unsupported push service.');

        // A browser belongs to whoever is signed in on it now.
        DB::table('push_subscriptions')->upsert([[
            'user_id' => Auth::id(),
            'endpoint_hash' => hash('sha256', $data['endpoint']),
            'endpoint' => $data['endpoint'],
            'p256dh' => $data['keys']['p256dh'],
            'auth' => $data['keys']['auth'],
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['endpoint_hash'], ['user_id', 'p256dh', 'auth', 'user_agent', 'updated_at']);

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:1000']]);
        DB::table('push_subscriptions')->where('user_id', Auth::id())->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();

        return response()->json(['ok' => true]);
    }

    public function joinWaitlist(Request $request, Show $show): RedirectResponse
    {
        $data = $request->validate(['seats' => ['required', 'integer', 'min:1', 'max:'.(int) config('bookmymovie.booking.max_seats_per_booking', 4)]]);

        $startsAt = Carbon::parse($show->show_date->toDateString().' '.$show->show_time);
        abort_if($show->status !== 'scheduled' || $startsAt->isPast(), 422, 'This show is no longer on sale.');

        // Upsert: joining twice updates the request and re-arms the alert.
        DB::table('show_waitlists')->upsert([[
            'show_id' => $show->id, 'user_id' => Auth::id(), 'seats_wanted' => $data['seats'],
            'notified_at' => null, 'created_at' => now(), 'updated_at' => now(),
        ]], ['show_id', 'user_id'], ['seats_wanted', 'notified_at', 'updated_at']);

        return back()->with('status', 'You are on the waitlist. We will notify you the moment '.$data['seats'].' '.Str::plural('seat', $data['seats']).' open up.');
    }

    public function leaveWaitlist(Show $show): RedirectResponse
    {
        DB::table('show_waitlists')->where('show_id', $show->id)->where('user_id', Auth::id())->delete();

        return back()->with('status', 'You have left the waitlist.');
    }
}
