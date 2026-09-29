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
 * Facts for Usher, the on-site assistant.
 *
 * Usher only answers its own pre-set questions (there is no free-text box),
 * so this endpoint takes no input at all and returns a small read-only
 * snapshot. The public part is shared through the cache; the personal part
 * (name, points, next show, watchlist, picks from the genres the customer
 * books) is built per request for the signed-in customer only and is sent
 * with Cache-Control: private, no-store so no proxy or browser cache keeps it.
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
                'first_name' => strtok((string) $user->name, ' '),
                'points' => (int) $user->loyalty_points,
                'bookings' => Booking::query()->where('user_id', $user->id)->where('booking_status', '!=', 'cancelled')->count(),
                'watchlist' => $this->watchlistOnSale($user->id),
                'pick' => $this->pickFor($user->id),
                'next' => $next ? [
                    'film' => $next->show->movie->title,
                    'number' => $next->booking_number,
                    'when' => $next->showStartsAt()?->format('D j M, g:i A'),
                    'in' => $next->showStartsAt()?->diffForHumans(),
                    'cinema' => $next->show->screen->theater->name,
                    'paid' => $next->payment_status === 'paid',
                ] : null,
            ] : null,
        ])->header('Cache-Control', $user ? 'private, no-store' : 'private, max-age=60');
    }

    /** Watchlist films that have a show on sale, soonest first (max 3). */
    private function watchlistOnSale(int $userId): array
    {
        return DB::table('wishlists as w')
            ->join('movies as m', 'm.id', '=', 'w.movie_id')
            ->join('shows as s', 's.movie_id', '=', 'm.id')
            ->where('w.user_id', $userId)
            ->where('s.status', 'scheduled')
            ->whereRaw('TIMESTAMP(s.show_date, s.show_time) > ?', [now()->toDateTimeString()])
            ->groupBy('m.id', 'm.title', 'm.slug')
            ->orderByRaw('MIN(TIMESTAMP(s.show_date, s.show_time))')
            ->limit(3)
            ->get(['m.title', 'm.slug', DB::raw('MIN(TIMESTAMP(s.show_date, s.show_time)) AS next_at')])
            ->map(fn ($row) => ['title' => $row->title, 'slug' => $row->slug, 'when' => Carbon::parse($row->next_at)->format('D j M, g:i A')])
            ->all();
    }

    /**
     * A film now showing in the genre this customer books most, that they
     * have not booked yet. Falls back to the best rated film they have not seen.
     */
    private function pickFor(int $userId): ?array
    {
        $seen = DB::table('bookings as b')->join('shows as s', 's.id', '=', 'b.show_id')
            ->where('b.user_id', $userId)->where('b.booking_status', '!=', 'cancelled')
            ->distinct()->pluck('s.movie_id');

        $genre = $seen->isEmpty() ? null : DB::table('movie_genres as mg')->join('genres as g', 'g.id', '=', 'mg.genre_id')
            ->whereIn('mg.movie_id', $seen)
            ->groupBy('g.id', 'g.name')
            ->orderByRaw('COUNT(*) DESC')
            ->first(['g.id', 'g.name']);

        $query = Movie::query()->where('status', 'now_showing')->whereNotIn('id', $seen)
            ->orderByDesc('average_rating')->orderByDesc('total_reviews');

        $movie = $genre ? (clone $query)->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))->first(['id', 'title', 'slug', 'average_rating']) : null;
        $movie ??= $query->first(['id', 'title', 'slug', 'average_rating']);

        return $movie ? [
            'title' => $movie->title,
            'slug' => $movie->slug,
            'rating' => round((float) $movie->average_rating, 1),
            'because' => $genre?->name,
        ] : null;
    }
}
