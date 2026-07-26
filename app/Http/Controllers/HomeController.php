<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $movies = Schema::hasTable('movies')
            ? Movie::query()
                ->with('genres')
                ->whereIn('status', ['now_showing', 'coming_soon'])
                ->latest('release_date')
                ->limit(20)
                ->get()
                ->map(fn (Movie $movie) => $movie->toCardArray())
                ->all()
            : [];

        $slides = Schema::hasTable('movies') && Schema::hasColumn('movies', 'hero_carousel_enabled')
            ? Movie::query()
                ->with('genres')
                ->where('hero_carousel_enabled', true)
                ->whereIn('status', ['now_showing', 'coming_soon'])
                ->orderBy('hero_sort_order')
                ->latest('release_date')
                ->limit(5)
                ->get()
                ->map(fn (Movie $movie) => $movie->toCardArray())
                ->all()
            : [];

        if ($slides === []) {
            $slides = array_slice($movies, 0, 3);
        }

        $movieCategories = Schema::hasTable('genres')
            ? Genre::query()
                ->with(['movies' => fn ($query) => $query
                    ->with('genres')
                    ->whereIn('status', ['now_showing', 'coming_soon'])
                    ->orderBy('release_date')
                    ->limit(4)])
                ->orderBy('id')
                ->get()
                ->map(fn (Genre $genre) => [
                    'name' => $genre->name,
                    'slug' => $genre->slug,
                    'movies' => $genre->movies->map(fn (Movie $movie) => $movie->toCardArray())->all(),
                ])
                ->filter(fn (array $category) => count($category['movies']) > 0)
                ->values()
                ->all()
            : [];

        $tickerMessages = Schema::hasTable('site_settings')
            ? collect(explode('|', SiteSetting::query()->where('key', 'home_ticker_messages')->value('value') ?? ''))
                ->map(fn (string $message) => trim($message))
                ->filter()
                ->values()
                ->all()
            : [];

        return view('public.index', compact('movies', 'slides', 'tickerMessages', 'movieCategories'));
    }
}
