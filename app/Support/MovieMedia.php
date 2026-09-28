<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links the bundled artwork and trailers in public/ to the seeded films:
 *
 *   public/images/movies/{slug}/card.webp   4:3 card, used on every movie card
 *   public/images/movies/{slug}/hero.webp   16:9 backdrop, home carousel and film page
 *   public/videos/trailers/{slug}-1.mp4     first trailer ({slug}-2.mp4 for a second cut)
 *
 * Films without a card fall back to the hero, then to the generated
 * typographic poster, so nothing ever shows an empty box. Admin uploads
 * (movie-media/…) are never overwritten.
 */
class MovieMedia
{
    /** Films with trailers lead the home carousel, in this order. */
    private const HERO_ORDER = [
        'the-last-cinema-on-empress-road',
        'the-quiet-meridian',
        'whistle-of-the-night-heron',
        'a-small-hour-of-grace',
        'kestrel',
    ];

    public static function attach(): void
    {
        if (! Schema::hasTable('movies')) {
            return;
        }

        $hasTrailers = Schema::hasColumn('movies', 'trailers');

        // Other carousel films queue up behind the ones with trailers.
        DB::table('movies')->whereNotIn('slug', self::HERO_ORDER)->where('hero_sort_order', '<', 10)->increment('hero_sort_order', 10);

        foreach (DB::table('movies')->get(['id', 'slug', 'poster_image', 'hero_image', 'banner_image']) as $movie) {
            $card = self::existing("images/movies/{$movie->slug}/card.webp");
            $hero = self::existing("images/movies/{$movie->slug}/hero.webp");
            $update = [];

            if (self::replaceable($movie->poster_image) && ($card || $hero)) {
                $update['poster_image'] = $card ?? $hero;
            }

            if (self::replaceable($movie->hero_image) && $hero) {
                $update['hero_image'] = $hero;
                $update['banner_image'] = $hero;
            }

            if ($hasTrailers) {
                $trailers = [];
                foreach ([1 => 'Trailer', 2 => 'Alternate cut'] as $index => $label) {
                    if ($src = self::existing("videos/trailers/{$movie->slug}-{$index}.mp4")) {
                        $poster = self::existing("videos/trailers/{$movie->slug}-{$index}.webp");
                        $trailers[] = ['src' => $src, 'label' => $label, 'poster' => $poster];
                    }
                }
                $update['trailers'] = $trailers ? json_encode($trailers, JSON_UNESCAPED_SLASHES) : null;
            }

            $position = array_search($movie->slug, self::HERO_ORDER, true);
            if ($position !== false && $hero) {
                $update['hero_carousel_enabled'] = true;
                $update['hero_sort_order'] = $position + 1;
            }

            if ($update) {
                DB::table('movies')->where('id', $movie->id)->update($update);
            }
        }
    }

    private static function existing(string $path): ?string
    {
        return is_file(public_path($path)) ? $path : null;
    }

    /** Empty, or one of our bundled files: safe to point at the new artwork. */
    private static function replaceable(?string $current): bool
    {
        $current = trim((string) $current);

        return $current === '' || str_starts_with($current, 'images/');
    }
}
