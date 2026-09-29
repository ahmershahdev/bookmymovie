<?php

namespace App\Console\Commands;

use App\Models\ContentPage;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Theater;
use App\Support\Seo;
use GdImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Builds the static SEO assets that are committed to the repository:
 *
 *   public/images/og/default.png          1200×1200 site card
 *   public/images/og/movies/{slug}.png    1200×1200 card per film
 *   public/sitemap.xml                    every indexable URL
 *
 * Square cards are used because they crop well on every network
 * (WhatsApp, X "summary", LinkedIn, Facebook) and in Google Discover.
 */
class GenerateSeoAssets extends Command
{
    protected $signature = 'seo:generate {--images : Only render Open Graph images} {--sitemap : Only write sitemap.xml and llms-full.txt}';

    protected $description = 'Render 1:1 Open Graph images and regenerate public/sitemap.xml and public/llms-full.txt';

    private const SIZE = 1200;

    private string $serif;

    private string $serifItalic;

    private string $mono;

    public function handle(): int
    {
        $both = ! $this->option('images') && ! $this->option('sitemap');

        if ($both || $this->option('images')) {
            if (! function_exists('imagettftext')) {
                $this->error('GD with FreeType is required to render images.');

                return self::FAILURE;
            }

            $this->serif = resource_path('fonts/InstrumentSerif-Regular.woff');
            $this->serifItalic = resource_path('fonts/InstrumentSerif-Italic.woff');
            $this->mono = resource_path('fonts/JetBrainsMono-Medium.woff');
            $this->renderImages();
        }

        if ($both || $this->option('sitemap')) {
            $this->writeSitemap();
            $this->writeLlmsFull();
        }

        return self::SUCCESS;
    }

    private function renderImages(): void
    {
        File::ensureDirectoryExists(public_path('images/og/movies'));

        $this->saveCard(public_path('images/og/default.png'), [
            'palette' => ['#15130f', '#d8b46a', '#f7f2e8'],
            'variant' => 0,
            'eyebrow' => 'CINEMA TICKETS · PAKISTAN',
            'title' => 'BookMyMovie',
            'tagline' => 'Every seat, every showtime, honestly priced.',
            'meta' => 'LIVE SEAT MAPS · NO BOOKING FEES',
            'corner' => 'BOOKMYMOVIE.AHMERSHAH.DEV',
        ]);
        $this->line('  default.png');

        $movies = Movie::query()->with('genres')->get();
        $bar = $this->output->createProgressBar($movies->count());

        foreach ($movies as $movie) {
            $this->saveCard(public_path('images/og/movies/'.$movie->slug.'.png'), [
                'palette' => $movie->posterPalette(),
                'variant' => crc32($movie->slug) % 4,
                'eyebrow' => strtoupper($movie->genres->pluck('name')->take(2)->join(' · ')),
                'title' => $movie->title,
                'tagline' => (string) $movie->tagline,
                'meta' => strtoupper($movie->durationLabel().' · '.$movie->certificate_rating.' · '.$movie->language.' · '.$movie->statusLabel()),
                'corner' => (string) ($movie->certificate_rating ?: 'NR'),
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Rendered {$movies->count()} film cards and the default card.");
    }

    /**
     * @param  array{palette: array{0: string, 1: string, 2: string}, variant: int, eyebrow: string, title: string, tagline: string, meta: string, corner: string}  $card
     */
    private function saveCard(string $path, array $card): void
    {
        [$groundHex, $accentHex, $paperHex] = $card['palette'];
        $image = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagealphablending($image, true);
        imageantialias($image, true);

        $ground = $this->rgb($groundHex);
        $accent = $this->rgb($accentHex);
        $paper = $this->rgb($paperHex);
        $ink = [11, 10, 9];

        // Background: vertical fade from the ground colour into near-black.
        for ($y = 0; $y < self::SIZE; $y++) {
            $t = $y / self::SIZE;
            imageline($image, 0, $y, self::SIZE, $y, $this->color($image, $this->mix($ground, $ink, $t * 0.75)));
        }

        // Accent glow, top right.
        for ($r = 900; $r > 0; $r -= 20) {
            $alpha = (int) (125 - (1 - $r / 900) * 9);
            imagefilledellipse($image, 1000, 150, $r, $r, imagecolorallocatealpha($image, $accent[0], $accent[1], $accent[2], max(0, min(127, $alpha))));
        }

        $this->drawMotif($image, $card['variant'], $accent, $ground, $paper);

        $paperColor = $this->color($image, $paper);
        $accentColor = $this->color($image, $accent);
        $muted = imagecolorallocatealpha($image, $paper[0], $paper[1], $paper[2], 50);
        $margin = 96;

        imagettftext($image, 20, 0, $margin, $margin + 20, $muted, $this->mono, 'BOOKMYMOVIE');
        $cornerBox = imagettfbbox(20, 0, $this->mono, $card['corner']);
        imagettftext($image, 20, 0, self::SIZE - $margin - ($cornerBox[2] - $cornerBox[0]), $margin + 20, $muted, $this->mono, $card['corner']);

        // Title block, laid out bottom-up so long titles push upwards.
        $titleSize = mb_strlen($card['title']) > 24 ? 92 : (mb_strlen($card['title']) > 14 ? 112 : 138);
        $titleLines = $this->wrap($card['title'], $this->serif, $titleSize, self::SIZE - $margin * 2);
        $taglineLines = $card['tagline'] !== '' ? $this->wrap($card['tagline'], $this->serifItalic, 40, self::SIZE - $margin * 2) : [];
        $lineHeight = (int) ($titleSize * 1.02);

        $y = self::SIZE - $margin;
        imagettftext($image, 18, 0, $margin, $y, $muted, $this->mono, $card['meta']);
        $y -= 44;
        imagefilledrectangle($image, $margin, $y, $margin + 72, $y + 2, $accentColor);
        $y -= 48;

        foreach (array_reverse(array_slice($taglineLines, 0, 2)) as $line) {
            imagettftext($image, 40, 0, $margin, $y, $muted, $this->serifItalic, $line);
            $y -= 54;
        }

        $y -= 18;
        foreach (array_reverse(array_slice($titleLines, 0, 3)) as $line) {
            imagettftext($image, $titleSize, 0, $margin - 4, $y, $paperColor, $this->serif, $line);
            $y -= $lineHeight;
        }

        // $y is now one line above the first title line; sit the eyebrow just over its cap height.
        imagettftext($image, 20, 0, $margin, $y + $lineHeight - (int) ($titleSize * 0.82) - 40, $accentColor, $this->mono, $card['eyebrow']);

        imagepng($image, $path, 9);
        imagedestroy($image);
    }

    private function drawMotif(GdImage $image, int $variant, array $accent, array $ground, array $paper): void
    {
        imagesetthickness($image, 3);

        match ($variant) {
            0 => (function () use ($image, $accent, $ground) {
                imagefilledellipse($image, 1010, 360, 620, 620, $this->color($image, $accent));
                for ($band = 0; $band < 6; $band++) {
                    imagefilledrectangle($image, 0, 470 + $band * 34, self::SIZE, 474 + $band * 34, imagecolorallocatealpha($image, $ground[0], $ground[1], $ground[2], (int) (20 + $band * 16)));
                }
            })(),
            1 => (function () use ($image, $accent, $paper) {
                for ($x = 0; $x < 260; $x++) {
                    imageline($image, 300 + $x, 0, 300 + $x, 760, imagecolorallocatealpha($image, $accent[0], $accent[1], $accent[2], 60 + (int) ($x / 260 * 30)));
                }
                imagefilledrectangle($image, 640, 0, 700, 620, imagecolorallocatealpha($image, $paper[0], $paper[1], $paper[2], 95));
            })(),
            2 => (function () use ($image, $accent) {
                imagettftext($image, 620, 0, 640, 720, imagecolorallocatealpha($image, $accent[0], $accent[1], $accent[2], 72), $this->serifItalic, 'b');
            })(),
            default => (function () use ($image, $accent) {
                for ($ring = 1; $ring <= 6; $ring++) {
                    imageellipse($image, 820, 380, 120 + $ring * 150, 120 + $ring * 150, imagecolorallocatealpha($image, $accent[0], $accent[1], $accent[2], 20 + $ring * 14));
                }
            })(),
        };
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text, string $font, int $size, int $width): array
    {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($size, 0, $font, $candidate);

            if ($box[2] - $box[0] > $width && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function writeSitemap(): void
    {
        $today = now()->toAtomString();
        $entries = [];
        $add = function (string $path, string $lastmod, string $frequency, string $priority, ?string $image = null, ?string $imageTitle = null) use (&$entries) {
            $entries[] = compact('path', 'lastmod', 'frequency', 'priority', 'image', 'imageTitle');
        };

        $add('/', $today, 'daily', '1.0', Seo::url('images/og/default.png'), 'BookMyMovie');
        $add('/movies', $today, 'daily', '0.9');
        foreach (['now-showing' => '0.9', 'coming-soon' => '0.8', 'ended' => '0.4'] as $status => $priority) {
            $add('/movies/'.$status, $today, 'daily', $priority);
        }
        $add('/cinemas', $today, 'weekly', '0.8');
        $add('/offers', $today, 'weekly', '0.6');
        $add('/gift-cards', $today, 'monthly', '0.5');
        $add('/compare', $today, 'weekly', '0.4');
        $add('/register', $today, 'yearly', '0.3');
        $add('/login', $today, 'yearly', '0.2');
        $add('/faq', $today, 'monthly', '0.6');
        $add('/contact', $today, 'yearly', '0.4');

        $routes = ['about' => '/about', 'terms' => '/terms', 'privacy' => '/privacy', 'refund' => '/refund-policy', 'eticket-info' => '/e-ticket-info', 'cookies' => '/cookie-policy', 'accessibility' => '/accessibility'];
        foreach (ContentPage::query()->where('is_active', true)->get() as $page) {
            if (isset($routes[$page->slug])) {
                $add($routes[$page->slug], $page->updated_at?->toAtomString() ?? $today, 'monthly', $page->slug === 'about' ? '0.6' : '0.3');
            }
        }

        foreach (Genre::query()->whereHas('movies', fn ($query) => $query->publiclyListed())->orderBy('slug')->get() as $genre) {
            $add('/genres/'.$genre->slug, $today, 'weekly', '0.6');
        }

        foreach (Movie::query()->whereIn('status', ['now_showing', 'coming_soon', 'ended'])->orderByRaw("FIELD(status, 'now_showing', 'coming_soon', 'ended')")->get() as $movie) {
            $add(
                '/movies/'.$movie->slug,
                $movie->updated_at?->toAtomString() ?? $today,
                $movie->status === 'ended' ? 'monthly' : 'daily',
                match ($movie->status) {
                    'now_showing' => '0.9',
                    'coming_soon' => '0.7',
                    default => '0.4',
                },
                Seo::url('images/og/movies/'.$movie->slug.'.png'),
                $movie->title
            );
        }

        foreach (Theater::query()->where('is_active', true)->orderBy('slug')->get() as $theater) {
            $add('/cinemas/'.$theater->slug, $theater->updated_at?->toAtomString() ?? $today, 'daily', '0.8');
        }

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($entries as $entry) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>'.e(Seo::url($entry['path'])).'</loc>';
            $xml[] = '    <lastmod>'.$entry['lastmod'].'</lastmod>';
            $xml[] = '    <changefreq>'.$entry['frequency'].'</changefreq>';
            $xml[] = '    <priority>'.$entry['priority'].'</priority>';
            if ($entry['image']) {
                $xml[] = '    <image:image><image:loc>'.e($entry['image']).'</image:loc><image:title>'.e($entry['imageTitle']).'</image:title></image:image>';
            }
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        File::put(public_path('sitemap.xml'), implode("\n", $xml)."\n");
        $this->info('Wrote public/sitemap.xml with '.count($entries).' URLs.');
    }


    /**
     * A plain-text, link-rich description of the whole site for AI assistants
     * (https://llmstxt.org). Generated so it always matches the catalogue.
     */
    private function writeLlmsFull(): void
    {
        $base = Seo::base();
        $out = [
            '# BookMyMovie',
            '',
            '> BookMyMovie is an open-source cinema ticketing platform for Pakistan. It lists films at partner cinemas in Karachi, Lahore, Islamabad and Rawalpindi, plays trailers, shows live seat maps with row-by-row prices, holds seats for '.config('bookmymovie.booking.cart_hold_minutes').' minutes and issues signed QR e-tickets that work offline. Customers pay at the cinema counter or online (JazzCash, Easypaisa or card where enabled). No booking fees are charged.',
            '',
            'Generated '.now()->toDateString().' from the live database. Canonical site: '.$base.'/',
            '',
            '## How booking works',
            '',
            '1. Choose a film at '.$base.'/movies and pick a showtime (the next 7 days are listed on each film page).',
            '2. Select up to '.config('bookmymovie.booking.max_seats_per_booking').' seats on the seat map. Seats are held for '.config('bookmymovie.booking.cart_hold_minutes').' minutes.',
            '3. At checkout, add snacks and use a coupon, gift card or loyalty points (5 points per PKR 100 paid; 1 point = PKR 1).',
            '4. Pay online, or at the cinema box office at least 20 minutes before the show. Group bookings can split the bill into equal shares.',
            '5. The QR e-ticket arrives at once; it can go into Apple or Google Wallet and stays readable offline.',
            '',
            'Unpaid bookings can be cancelled online until 2 hours before the show; seats, coupon use, gift card balance and points are returned. Sold-out shows have a first-come waitlist that is notified when seats come back.',
            '',
            'Pricing: front rows are cheapest, the centre rows ("Prime Centre") have the best sightlines and the back rows are recliners. IMAX, Dolby Cinema, 4DX and ScreenX screens cost more than standard 2D. Weekday shows before 5 PM carry a 15% matinee discount. Child tickets (ages 3-12) cost about 25% less and are not sold on recliner rows.',
            '',
            '## Films',
            '',
        ];

        $movies = Movie::query()->with(['genres', 'credits.person'])->orderByRaw("FIELD(status, 'now_showing', 'coming_soon', 'ended')")->orderBy('title')->get();

        foreach ($movies as $movie) {
            array_push($out,
                '### '.$movie->title.' ('.$movie->statusLabel().')',
                '',
                '- URL: '.$base.'/movies/'.$movie->slug,
                '- Tagline: '.$movie->tagline,
                '- Genres: '.$movie->genres->pluck('name')->join(', '),
                '- Runtime: '.$movie->duration_minutes.' minutes; certificate '.$movie->certificate_rating.'; language '.$movie->language,
                '- Release date: '.$movie->release_date?->toDateString(),
                '- Director: '.$movie->directorNames(),
                '- Cast: '.$movie->creditsFor('cast')->map(fn ($credit) => $credit->person->name.' as '.$credit->character_name)->join('; '),
            );

            foreach ($movie->trailers ?? [] as $trailer) {
                $out[] = '- '.($trailer['label'] ?? 'Trailer').': '.$base.'/'.ltrim((string) $trailer['src'], '/');
            }

            if ($movie->total_reviews > 0) {
                $out[] = '- Audience rating: '.number_format((float) $movie->average_rating, 1).'/5 from '.$movie->total_reviews.' verified reviews';
            }

            array_push($out, '- Synopsis: '.$movie->description, '');
        }

        array_push($out, '## Cinemas', '');

        foreach (Theater::query()->with(['city', 'screens', 'amenities'])->where('is_active', true)->orderBy('name')->get() as $theater) {
            array_push($out,
                '### '.$theater->name.', '.$theater->city?->name,
                '',
                '- URL: '.$base.'/cinemas/'.$theater->slug,
                '- Address: '.$theater->address,
                '- Screens: '.$theater->screens->map(fn ($screen) => $screen->screen_name.' ('.$screen->formatLabel().', '.$screen->total_seats.' seats)')->join('; '),
                '- Facilities: '.$theater->amenities->pluck('name')->join(', '),
                '- About: '.$theater->description,
                '',
            );
        }

        array_push($out, '## Current offers', '');

        foreach (\App\Models\Coupon::query()->where('is_active', true)->where('valid_until', '>=', now())->get() as $coupon) {
            $out[] = '- '.$coupon->code.': '.$coupon->description.' Valid until '.$coupon->valid_until->toDateString().'.';
        }

        array_push($out, '', '## Frequently asked questions', '');

        foreach (\App\Models\Faq::query()->where('is_active', true)->orderBy('sort_order')->get() as $faq) {
            array_push($out, '**'.$faq->question.'** '.$faq->answer, '');
        }

        array_push($out, '## Policies', '');

        foreach (['terms' => '/terms', 'privacy' => '/privacy', 'refund' => '/refund-policy', 'eticket-info' => '/e-ticket-info', 'cookies' => '/cookie-policy', 'accessibility' => '/accessibility'] as $slug => $path) {
            if ($page = ContentPage::query()->where('slug', $slug)->first()) {
                $out[] = '- ['.$page->title.']('.$base.$path.'): '.$page->excerpt;
            }
        }

        array_push($out,
            '',
            '## Project',
            '',
            '- Source code (MIT licence): https://github.com/ahmershahdev/bookmymovie',
            '- Author: Syed Ahmer Shah',
            '- Stack: Laravel 12, PHP 8.2+, MySQL 8 / MariaDB 10.4+, Inertia 2, React 19, TypeScript, Tailwind CSS 4, Three.js',
            '- Languages: English and Urdu (right-to-left)',
            '',
        );

        File::put(public_path('llms-full.txt'), implode("\n", $out));
        $this->info('Wrote public/llms-full.txt ('.$movies->count().' films).');
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private function mix(array $from, array $to, float $t): array
    {
        return array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $t), $from, $to);
    }

    private function color(GdImage $image, array $rgb): int
    {
        return imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    }
}
