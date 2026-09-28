<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Movie;
use App\Models\Screen;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Facts for the on-site assistant. It only answers pre-set questions, so
 * this returns a small, read-only snapshot: nothing a visitor types is ever
 * sent anywhere, and nothing here needs a language model.
 */
class AssistantController extends Controller
{
    public function context(): JsonResponse
    {
        $public = Cache::remember('assistant.context', now()->addMinutes(5), function () {
            $top = Movie::query()->where('status', 'now_showing')->orderByDesc('average_rating')->orderByDesc('total_reviews')->first(['title', 'slug', 'average_rating', 'total_reviews']);

            $cheapest = DB::table('v_show_details as s')
                ->join('show_seat_row_prices as p', 'p.show_id', '=', 's.show_id')
                ->where('s.show_status', 'scheduled')
                ->where('s.show_date', now()->toDateString())
                ->where('s.show_time', '>', now()->format('H:i:s'))
                ->orderByRaw('COALESCE(p.sale_price, p.price)')
                ->first(['s.movie_title', 's.movie_slug', 's.show_time', 's.theater_name', DB::raw('COALESCE(p.sale_price, p.price) AS price')]);

            $formats = DB::table('screens')->join('theaters', 'theaters.id', '=', 'screens.theater_id')
                ->where('screens.is_active', true)->whereIn('screens.format', ['imax', 'dolby_cinema', '4dx', 'screenx', 'recliner'])
                ->get(['screens.format', 'theaters.name'])
                ->groupBy('format')
                ->map(fn ($rows, $format) => ['format' => Screen::FORMAT_LABELS[$format] ?? $format, 'cinemas' => $rows->pluck('name')->unique()->values()->all()])
                ->values()
                ->all();

            return [
                'top' => $top ? ['title' => $top->title, 'slug' => $top->slug, 'rating' => round((float) $top->average_rating, 1), 'reviews' => (int) $top->total_reviews] : null,
                'cheapest' => $cheapest ? ['title' => $cheapest->movie_title, 'slug' => $cheapest->movie_slug, 'time' => Carbon::parse($cheapest->show_time)->format('g:i A'), 'cinema' => $cheapest->theater_name, 'price' => (float) $cheapest->price] : null,
                'formats' => $formats,
                'nowShowing' => Movie::query()->where('status', 'now_showing')->count(),
                'offers' => Coupon::query()->where('is_active', true)->where('valid_from', '<=', now())->where('valid_until', '>=', now())->count(),
            ];
        });

        $user = Auth::user();
        $next = $user ? Booking::query()
            ->with('show.movie:id,title,slug', 'show.screen.theater:id,name')
            ->where('user_id', $user->id)
            ->where('booking_status', 'confirmed')
            ->whereHas('show', fn ($query) => $query->whereRaw('TIMESTAMP(show_date, show_time) > ?', [now()->toDateTimeString()]))
            ->get()
            ->sortBy(fn (Booking $booking) => $booking->showStartsAt())
            ->first() : null;

        return response()->json([
            ...$public,
            'user' => $user ? [
                'first_name' => strtok($user->name, ' '),
                'points' => (int) $user->loyalty_points,
                'next' => $next ? [
                    'film' => $next->show->movie->title,
                    'number' => $next->booking_number,
                    'when' => $next->showStartsAt()?->format('D j M, g:i A'),
                    'in' => $next->showStartsAt()?->diffForHumans(),
                    'cinema' => $next->show->screen->theater->name,
                    'paid' => $next->payment_status === 'paid',
                ] : null,
            ] : null,
        ]);
    }
}
