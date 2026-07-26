<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'genre' => ['nullable', 'string', 'max:120', 'exists:genres,slug'],
            'language' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['now_showing', 'coming_soon', 'ended'])],
            'certificate' => ['nullable', Rule::in(['U', 'UA', 'A', 'S', 'G', 'PG', 'PG-13', 'R'])],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $searchLike = addcslashes($search, '\\%_');

        $movies = Movie::query()
            ->with('genres')
            ->when($search !== '', function ($builder) use ($searchLike) {
                $builder->where(function ($builder) use ($searchLike) {
                    $builder->where('title', 'like', "%{$searchLike}%")
                        ->orWhere('description', 'like', "%{$searchLike}%");
                });
            })
            ->when(! empty($filters['genre']), function ($builder) use ($filters) {
                $builder->whereHas('genres', fn ($genres) => $genres->where('slug', $filters['genre']));
            })
            ->when(! empty($filters['language']), fn ($builder) => $builder->where('language', $filters['language']))
            ->when(! empty($filters['status']), fn ($builder) => $builder->where('status', $filters['status']))
            ->when(! empty($filters['certificate']), fn ($builder) => $builder->where('certificate_rating', $filters['certificate']))
            ->latest('release_date')
            ->paginate(12)
            ->withQueryString();

        return view('movies.index', [
            'movieCards' => $movies->getCollection()->map(fn (Movie $movie) => $movie->toCardArray()),
            'moviesPaginator' => $movies,
            'genres' => Genre::query()->orderBy('name')->get(),
            'languages' => Movie::query()->select('language')->distinct()->orderBy('language')->pluck('language'),
            'certificates' => Movie::query()->whereNotNull('certificate_rating')->select('certificate_rating')->distinct()->orderBy('certificate_rating')->pluck('certificate_rating'),
        ]);
    }

    public function show(string $slug): View
    {
        $movie = Movie::query()
            ->with(['genres', 'reviews' => fn ($query) => $query->where('is_approved', true)->with('user')->latest()->limit(5)])
            ->where('slug', $slug)
            ->firstOrFail();

        $shows = DB::table('v_show_details')
            ->where('movie_slug', $movie->slug)
            ->where('show_status', 'scheduled')
            ->whereDate('show_date', '>=', now()->toDateString())
            ->orderBy('show_date')
            ->orderBy('show_time')
            ->limit(12)
            ->get();

        return view('movies.show', compact('movie', 'shows'));
    }

    public function seats(string $slug, int $show): View
    {
        $movie = Movie::query()->where('slug', $slug)->firstOrFail();

        $showDetails = DB::table('v_show_details')
            ->where('show_id', $show)
            ->where('movie_slug', $slug)
            ->first();

        abort_unless($showDetails, 404);

        $seats = DB::table('v_seat_availability')
            ->where('show_id', $show)
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get();

        $pricingTiers = $seats
            ->groupBy('row_label')
            ->map(function ($tierSeats, string $rowLabel) {
                $rows = $tierSeats->pluck('row_label')->unique()->values();

                return [
                    'category' => $tierSeats->first()->row_tier_name ?: $tierSeats->first()->category_name,
                    'rows' => $rows->join(', '),
                    'benefits' => $tierSeats->first()->row_benefits,
                    'is_front' => in_array($rowLabel, ['A', 'B'], true),
                    'price' => (float) $tierSeats->first()->price,
                    'sale_price' => $tierSeats->first()->sale_price !== null ? (float) $tierSeats->first()->sale_price : null,
                    'kids_price' => $tierSeats->first()->kids_price !== null ? (float) $tierSeats->first()->kids_price : null,
                    'kids_sale_price' => $tierSeats->first()->kids_sale_price !== null ? (float) $tierSeats->first()->kids_sale_price : null,
                ];
            })
            ->sortBy('rows')
            ->values();

        return view('movies.seats', [
            'movie' => $movie,
            'showModel' => Show::findOrFail($show),
            'showDetails' => $showDetails,
            'seats' => $seats,
            'pricingTiers' => $pricingTiers,
            'slug' => $slug,
            'show' => $show,
        ]);
    }
}
