<?php

namespace App\Http\Controllers;

use App\Models\Movie;
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
                ->limit(10)
                ->get()
                ->map(fn (Movie $movie) => $movie->toCardArray())
                ->all()
            : [];

        $slides = array_slice($movies, 0, 3);

        $tickerMessages = [
            'Sale is live: premium seats from PKR 650 today.',
            'Your wait is finished: Thunder Protocol is now showing.',
            'Weekend family bookings get kids discount on Little Heroes.',
            'New Karachi and Lahore shows added every evening.',
            'Wishlist your next movie and book seats faster.',
        ];

        return view('public.index', compact('movies', 'slides', 'tickerMessages'));
    }
}
