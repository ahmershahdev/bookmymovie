<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

/**
 * Revenue and occupancy for the owner and staff. Figures come straight from
 * bookings and shows (cancelled bookings excluded) and are cached for five
 * minutes per range, so the page stays cheap however often it is opened.
 */
class AdminAnalyticsController extends AdminController
{
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            return redirect()->route('admin.login');
        }

        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;

        $data = Cache::remember('admin.analytics.'.$days, now()->addMinutes(5), function () use ($days) {
            $from = now()->subDays($days - 1)->startOfDay();

            $daily = DB::table('bookings')
                ->where('booking_status', '<>', 'cancelled')
                ->where('booked_at', '>=', $from)
                ->selectRaw('DATE(booked_at) AS day, SUM(total_amount + gift_card_amount) AS revenue, SUM(seat_count) AS tickets, COUNT(*) AS bookings')
                ->groupBy('day')
                ->get()
                ->keyBy('day');

            $series = collect(range(0, $days - 1))->map(function (int $offset) use ($from, $daily) {
                $day = $from->copy()->addDays($offset)->toDateString();
                $row = $daily[$day] ?? null;

                return [
                    'day' => $day,
                    'label' => Carbon::parse($day)->format('D j M'),
                    'revenue' => round((float) ($row->revenue ?? 0), 2),
                    'tickets' => (int) ($row->tickets ?? 0),
                    'bookings' => (int) ($row->bookings ?? 0),
                ];
            })->values();

            $occupancy = DB::table('shows')
                ->join('movies', 'movies.id', '=', 'shows.movie_id')
                ->where('shows.status', '<>', 'cancelled')
                ->whereBetween('shows.show_date', [$from->toDateString(), now()->addDays(14)->toDateString()])
                ->where('shows.total_seats', '>', 0)
                ->selectRaw('movies.title, movies.slug, SUM(shows.booked_seats) AS sold, SUM(shows.total_seats) AS capacity, COUNT(*) AS shows')
                ->groupBy('movies.id', 'movies.title', 'movies.slug')
                ->get()
                ->map(fn ($row) => [
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'sold' => (int) $row->sold,
                    'capacity' => (int) $row->capacity,
                    'shows' => (int) $row->shows,
                    'rate' => $row->capacity ? round($row->sold / $row->capacity * 100, 1) : 0,
                ])
                ->sortByDesc('rate')
                ->values();

            $byCinema = DB::table('shows')
                ->join('screens', 'screens.id', '=', 'shows.screen_id')
                ->join('theaters', 'theaters.id', '=', 'screens.theater_id')
                ->where('shows.status', '<>', 'cancelled')
                ->whereBetween('shows.show_date', [$from->toDateString(), now()->addDays(14)->toDateString()])
                ->selectRaw('theaters.name, SUM(shows.booked_seats) AS sold, SUM(shows.total_seats) AS capacity')
                ->groupBy('theaters.id', 'theaters.name')
                ->get()
                ->map(fn ($row) => ['name' => $row->name, 'sold' => (int) $row->sold, 'capacity' => (int) $row->capacity, 'rate' => $row->capacity ? round($row->sold / $row->capacity * 100, 1) : 0])
                ->sortByDesc('rate')
                ->values();

            $revenue = $series->sum('revenue');
            $tickets = $series->sum('tickets');
            $previous = (float) DB::table('bookings')->where('booking_status', '<>', 'cancelled')
                ->whereBetween('booked_at', [$from->copy()->subDays($days), $from])->sum(DB::raw('total_amount + gift_card_amount'));
            $sold = $occupancy->sum('sold');
            $capacity = $occupancy->sum('capacity');

            return [
                'series' => $series,
                'occupancy' => $occupancy,
                'cinemas' => $byCinema,
                'totals' => [
                    'revenue' => round($revenue, 2),
                    'revenue_change' => $previous > 0 ? round(($revenue - $previous) / $previous * 100, 1) : null,
                    'tickets' => $tickets,
                    'bookings' => $series->sum('bookings'),
                    'average_ticket' => $tickets ? round($revenue / $tickets, 2) : 0,
                    'occupancy' => $capacity ? round($sold / $capacity * 100, 1) : 0,
                ],
            ];
        });

        return $this->page('Admin/Analytics', [...$data, 'days' => $days], ['title' => 'Revenue & occupancy | Admin', 'description' => 'Revenue and occupancy.', 'robots' => 'noindex, nofollow']);
    }
}
