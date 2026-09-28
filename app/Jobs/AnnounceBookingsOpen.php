<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Support\Notify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

/** Tells everyone who saved a film that tickets are now on sale. */
class AnnounceBookingsOpen implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $movieId) {}

    public function handle(): void
    {
        $movie = Movie::query()->find($this->movieId);
        if (! $movie) {
            return;
        }

        DB::table('wishlists')->where('movie_id', $movie->id)->orderBy('user_id')->select('user_id')
            ->chunk(500, fn ($rows) => Notify::users(
                $rows->pluck('user_id')->map(fn ($id) => (int) $id)->all(),
                'film_open',
                $movie->title.' is open for booking',
                'A film on your watchlist now has showtimes. Good seats go first.',
                route('movies.show', $movie->slug).'#showtimes',
                'movie',
                $movie->id,
            ));
    }
}
