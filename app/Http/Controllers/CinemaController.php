<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Screen;
use App\Models\Theater;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Response;

class CinemaController extends Controller
{
    private const AMENITY_ICONS = [
        'imax-with-laser' => 'screen', 'dolby-atmos' => 'sound', 'recliner-seating' => 'seat', 'wheelchair-access' => 'wheelchair',
        'parking' => 'car', 'food-court' => 'coffee', 'kids-play-area' => 'child', 'prayer-room' => 'prayer', 'closed-captions' => 'captions',
    ];

    public function index(Request $request): Response
    {
        $cinemas = DB::table('v_theater_catalog')->where('is_active', true)->orderBy('city')->orderBy('name')->get();

        $formats = DB::table('screens')
            ->where('is_active', true)
            ->selectRaw('theater_id, GROUP_CONCAT(DISTINCT format ORDER BY format) AS formats')
            ->groupBy('theater_id')
            ->pluck('formats', 'theater_id');

        $showsToday = DB::table('v_show_details')
            ->where('show_status', 'scheduled')
            ->where('show_date', now()->toDateString())
            ->selectRaw('theater_id, COUNT(*) AS total')
            ->groupBy('theater_id')
            ->pluck('total', 'theater_id');

        $cities = $cinemas->groupBy('city')->map(fn (Collection $venues, string $city) => [
            'name' => $city,
            'slug' => Str::slug($city),
            'province' => $venues->first()->province,
            'cinemas' => $venues->map(fn ($cinema) => [
                'slug' => $cinema->slug,
                'name' => $cinema->name,
                'address' => $cinema->address,
                'amenities' => $cinema->amenity_list,
                'screens' => (int) $cinema->screen_count,
                'seats' => (int) $cinema->seat_capacity,
                'today' => (int) ($showsToday[$cinema->theater_id] ?? 0),
                'formats' => collect(explode(',', (string) ($formats[$cinema->theater_id] ?? '')))
                    ->filter()
                    ->map(fn ($format) => Screen::FORMAT_LABELS[$format] ?? $format)
                    ->values(),
            ])->values(),
        ])->values();

        return $this->page('Cinemas/Index', [
            'cities' => $cities,
            'totals' => [
                'cinemas' => $cinemas->count(),
                'screens' => (int) $cinemas->sum('screen_count'),
                'seats' => (int) $cinemas->sum('seat_capacity'),
                'cities' => $cinemas->pluck('city')->unique()->count(),
            ],
        ], [
            'title' => 'Cinemas in Karachi, Lahore & Islamabad | BookMyMovie',
            'description' => 'Six partner cinemas across four cities with IMAX, Dolby Cinema, 4DX, ScreenX and recliner screens. Find showtimes by venue.',
            'schema' => [[
                '@type' => 'ItemList',
                'name' => 'BookMyMovie partner cinemas',
                'itemListElement' => $cinemas->values()->map(fn ($cinema, $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => Seo::url('cinemas/'.$cinema->slug),
                    'name' => $cinema->name,
                ])->all(),
            ]],
        ]);
    }

    public function show(Theater $theater): Response
    {
        abort_unless($theater->is_active, 404);

        $theater->load(['city', 'amenities', 'screens' => fn ($query) => $query->where('is_active', true)->orderBy('screen_name')]);

        $days = collect(range(0, 6))->map(fn (int $offset) => now()->startOfDay()->addDays($offset));

        $week = DB::table('v_show_details')
            ->where('theater_id', $theater->id)
            ->whereBetween('show_date', [$days->first()->toDateString(), $days->last()->toDateString()])
            ->where('show_status', 'scheduled')
            ->whereRaw('TIMESTAMP(show_date, show_time) > ?', [now()->toDateTimeString()])
            ->orderBy('movie_title')
            ->orderBy('show_time')
            ->get();

        $movies = Movie::query()
            ->withCardMetrics()
            ->with('genres')
            ->whereIn('id', $week->pluck('movie_id')->unique())
            ->get()
            ->keyBy('id');

        $city = $theater->city?->name ?? '';

        return $this->page('Cinemas/Show', [
            'theater' => [
                'name' => $theater->name,
                'slug' => $theater->slug,
                'description' => $theater->description,
                'address' => $theater->address,
                'city' => $city,
                'province' => $theater->city?->province,
                'hours' => 'Daily '.Carbon::parse($theater->opens_at)->format('g:i A').' – '.Carbon::parse($theater->closes_at)->format('g:i A'),
                'phone' => $theater->phone,
                'map_url' => 'https://www.google.com/maps/search/?api=1&query='.urlencode($theater->name.', '.$theater->address),
                'amenities' => $theater->amenities->map(fn ($amenity) => [
                    'name' => $amenity->name,
                    'description' => $amenity->description,
                    'icon' => self::AMENITY_ICONS[$amenity->slug] ?? 'sparkle',
                ])->values(),
                'screens' => $theater->screens->map(fn (Screen $screen) => [
                    'name' => $screen->screen_name,
                    'format' => $screen->formatLabel(),
                    'sound' => $screen->sound_system,
                    'seats' => (int) $screen->total_seats,
                ])->values(),
            ],
            'days' => $days->map(fn (Carbon $day) => [
                'date' => $day->toDateString(),
                'weekday' => $day->isToday() ? 'Today' : $day->format('D'),
                'day' => $day->format('j'),
                'schedule' => $week
                    ->where('show_date', $day->toDateString())
                    ->groupBy('movie_id')
                    ->filter(fn (Collection $shows, $movieId) => isset($movies[$movieId]))
                    ->map(fn (Collection $shows, $movieId) => [
                        'movie' => $movies[$movieId]->toCardArray(0),
                        'shows' => $shows->map(fn ($show) => [
                            'id' => $show->show_id,
                            'time' => Carbon::parse($show->show_time)->format('g:i A'),
                            'screen' => $show->screen_name,
                            'format' => Screen::FORMAT_LABELS[$show->screen_format] ?? 'Standard 2D',
                            'left' => (int) $show->available_seats,
                        ])->values(),
                    ])
                    ->values(),
            ])->values(),
            'nearby' => DB::table('v_theater_catalog')
                ->where('is_active', true)
                ->where('theater_id', '<>', $theater->id)
                ->orderByRaw('city = ? DESC', [$city])
                ->limit(3)
                ->get()
                ->map(fn ($cinema) => ['slug' => $cinema->slug, 'name' => $cinema->name, 'city' => $cinema->city]),
        ], [
            'title' => $theater->name.', '.$city.': Showtimes & Tickets',
            'description' => $theater->name.' in '.$city.'. '.$theater->screens->count().' screens, today’s showtimes, seat prices, facilities and directions.',
            'schema' => [Seo::theater($theater)],
        ], [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Cinemas', 'url' => route('cinemas.index')],
            ['label' => $city ?: 'City', 'url' => route('cinemas.index').'#city-'.Str::slug($city)],
            ['label' => $theater->name, 'url' => null],
        ]);
    }
}
