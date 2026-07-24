<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function index(Request $request): View
    {
        $movies = Movie::query()
            ->with('genres')
            ->when($request->filled('genre'), function ($builder) use ($request) {
                $builder->whereHas('genres', fn ($genres) => $genres->where('slug', $request->string('genre')));
            })
            ->when($request->filled('language'), fn ($builder) => $builder->where('language', $request->string('language')))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))
            ->when($request->filled('certificate'), fn ($builder) => $builder->where('certificate_rating', $request->string('certificate')))
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

        return view('movies.seats', [
            'movie' => $movie,
            'showModel' => Show::findOrFail($show),
            'showDetails' => $showDetails,
            'seats' => $seats,
            'slug' => $slug,
            'show' => $show,
        ]);
    }
}
