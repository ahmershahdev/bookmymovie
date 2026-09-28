<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use App\Support\Seo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $cards = fn ($query) => $query->withCardMetrics()->with('genres')->get()->map(fn (Movie $movie) => $movie->toCardArray());

        // The catalogue rails are the same for everyone; build them once every few minutes.
        [$slides, $nowShowing, $comingSoon] = Cache::remember('home.rails', now()->addMinutes(5), fn () => [
            $cards(Movie::query()->where('hero_carousel_enabled', true)->publiclyListed()->orderBy('hero_sort_order')->limit(5)),
            $cards(Movie::query()->where('status', 'now_showing')->orderByDesc('total_reviews')->orderByDesc('average_rating')),
            $cards(Movie::query()->where('status', 'coming_soon')->orderBy('release_date')),
        ]);

        $topRated = $nowShowing->sortByDesc('rating')->take(3)->values();

        $stats = Cache::remember('home.stats', now()->addMinutes(15), fn () => [
            'films' => Movie::query()->publiclyListed()->count(),
            'cinemas' => DB::table('theaters')->where('is_active', true)->count(),
            'screens' => DB::table('screens')->where('is_active', true)->count(),
            'cities' => DB::table('cities')->whereIn('id', DB::table('theaters')->select('city_id'))->count(),
            'shows_this_week' => DB::table('shows')->where('status', 'scheduled')->whereBetween('show_date', [now()->toDateString(), now()->addDays(6)->toDateString()])->count(),
            'tickets_sold' => (int) DB::table('booking_seats')->where('seat_lock', 1)->count(),
        ]);

        $slides = $slides->isNotEmpty() ? $slides : $nowShowing->take(3);

        return $this->page('Home', [
            'slides' => $slides->values(),
            'nowShowing' => $nowShowing->values(),
            'comingSoon' => $comingSoon->values(),
            'topRated' => $topRated,
            'genres' => Genre::query()
                ->withCount(['movies' => fn ($query) => $query->publiclyListed()])
                ->orderByDesc('movies_count')
                ->get()
                ->filter(fn (Genre $genre) => $genre->movies_count > 0)
                ->map(fn (Genre $genre) => ['name' => $genre->name, 'slug' => $genre->slug, 'count' => $genre->movies_count])
                ->values(),
            'cinemas' => DB::table('v_theater_catalog')->where('is_active', true)->orderBy('city')->get()
                ->map(fn ($cinema) => [
                    'slug' => $cinema->slug,
                    'name' => $cinema->name,
                    'city' => $cinema->city,
                    'screens' => (int) $cinema->screen_count,
                    'seats' => (int) $cinema->seat_capacity,
                    'address' => Str::limit((string) $cinema->address, 60),
                    'amenities' => array_slice(array_filter(array_map('trim', explode(',', (string) $cinema->amenity_list))), 0, 3),
                ]),
            'offers' => Coupon::query()
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_until', '>=', now())
                ->orderBy('min_order_amount')
                ->get()
                ->map(fn (Coupon $offer) => [
                    'code' => $offer->code,
                    'description' => $offer->description,
                    'value' => $offer->discount_type === 'percentage'
                        ? rtrim(rtrim(number_format((float) $offer->discount_value, 2), '0'), '.').'%'
                        : 'PKR '.number_format((float) $offer->discount_value),
                ]),
            'reviews' => Review::query()
                ->with(['user:id,name', 'movie:id,title,slug'])
                ->where('is_approved', true)
                ->where('rating', '>=', 4)
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (Review $review) => [
                    'id' => $review->id,
                    'text' => $review->review_text,
                    'rating' => (int) $review->rating,
                    'author' => Str::before($review->user->name, ' ').' '.mb_substr(Str::after($review->user->name, ' '), 0, 1).'.',
                    'movie' => ['title' => $review->movie->title, 'slug' => $review->movie->slug],
                ]),
            'faqs' => Faq::query()->where('is_active', true)->orderBy('sort_order')->limit(5)->get(['id', 'question', 'answer']),
            'stats' => $stats,
            'ticker' => collect(explode('|', (string) ($this->setting('home_ticker_messages') ?? '')))
                ->map(fn (string $message) => trim($message))
                ->filter()
                ->values(),
        ], [
            'title' => 'BookMyMovie | Cinema Tickets, Showtimes & Seats',
            'description' => 'Book cinema tickets across Pakistan with live seat maps, honest row pricing, IMAX and Dolby showtimes, and no booking fees.',
            'schema' => [[
                '@type' => 'ItemList',
                'name' => 'Now showing',
                'itemListElement' => $nowShowing->take(10)->values()->map(fn ($movie, $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => Seo::url('movies/'.$movie['slug']),
                    'name' => $movie['title'],
                ])->all(),
            ]],
        ]);
    }

    private function setting(string $key): ?string
    {
        return DB::table('site_settings')->where('key', $key)->value('value');
    }
}
