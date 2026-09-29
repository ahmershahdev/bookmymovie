<?php

namespace App\Support;

use App\Models\Movie;
use App\Models\Theater;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Metadata rules applied site-wide:
 *  - <title> at most 60 characters, description at most 150, both clipped at
 *    a word boundary rather than mid-word.
 *  - Open Graph / Twitter images are 1:1 (1200 × 1200).
 *  - JSON-LD uses schema.org types that match the page: Movie with
 *    ScreeningEvent offers, MovieTheater, FAQPage, BreadcrumbList, etc.
 */
class Seo
{
    public const TITLE_MAX = 60;

    public const DESCRIPTION_MAX = 150;

    public const IMAGE_SIZE = 1200;

    public static function clip(?string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');

        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " ,;:.-–—|").'…';
    }

    public static function title(?string $title): string
    {
        return self::clip($title, self::TITLE_MAX);
    }

    public static function description(?string $description): string
    {
        return self::clip($description, self::DESCRIPTION_MAX);
    }

    public static function base(): string
    {
        return rtrim((string) config('bookmymovie.canonical_url'), '/');
    }

    public static function url(string $path = '/'): string
    {
        return self::base().'/'.ltrim($path, '/');
    }

    public static function defaultImage(): string
    {
        return self::url('images/og/default.png');
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(array $settings = []): array
    {
        return [
            '@type' => 'Organization',
            '@id' => self::url('#organization'),
            'name' => $settings['site_name'] ?? 'BookMyMovie',
            'url' => self::url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => self::url('images/favicon/android-chrome-512x512.png'),
                'width' => 512,
                'height' => 512,
            ],
            'email' => $settings['support_email'] ?? 'support@ahmershah.dev',
            'sameAs' => ['https://ahmershah.dev/', 'https://github.com/ahmershahdev', 'https://github.com/ahmershahdev/bookmymovie', 'https://linkedin.com/in/syedahmershah'],
            'founder' => ['@type' => 'Person', 'name' => 'Syed Ahmer Shah', 'url' => 'https://ahmershah.dev/', 'sameAs' => ['https://github.com/ahmershahdev', 'https://linkedin.com/in/syedahmershah']],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $settings['support_email'] ?? 'support@ahmershah.dev',
                'telephone' => $settings['support_phone'] ?? null,
                'areaServed' => 'PK',
                'availableLanguage' => ['English', 'Urdu'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(array $settings = []): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::url('#website'),
            'name' => $settings['site_name'] ?? 'BookMyMovie',
            'url' => self::url('/'),
            'inLanguage' => 'en',
            'publisher' => ['@id' => self::url('#organization')],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => self::url('search').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  list<array{label: string, url: ?string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['label'],
                'item' => $item['url'] ? self::canonicalize($item['url']) : null,
            ]))->all(),
        ];
    }

    /**
     * @param  iterable<object>  $shows  rows from v_show_details
     * @return array<string, mixed>
     */
    /**
     * A <link rel="preload"> for the page's largest image (LCP), with the same
     * srcset the React <img> uses (card/hero ship -sm copies), so the browser
     * starts the download before the JavaScript has even run.
     *
     * @return array{href: string, srcset: ?string, sizes: string}|null
     */
    public static function preloadImage(?string $url, string $sizes): ?array
    {
        if (! $url) {
            return null;
        }

        $srcset = null;
        if (preg_match('#^(.*/(card|hero))\.webp$#', $url, $match)) {
            $srcset = $match[2] === 'card'
                ? "{$match[1]}-sm.webp 640w, {$match[1]}.webp 1200w"
                : "{$match[1]}-sm.webp 960w, {$match[1]}.webp 1600w";
        }

        return ['href' => $url, 'srcset' => $srcset, 'sizes' => $sizes];
    }

    public static function movie(Movie $movie, iterable $shows = []): array
    {
        $rating = $movie->verifiedRating();
        $url = self::url('movies/'.$movie->slug);

        return array_filter([
            '@type' => 'Movie',
            '@id' => $url.'#movie',
            'name' => $movie->title,
            'url' => $url,
            'description' => self::clip($movie->description, 300),
            'image' => $movie->ogImageUrl(),
            'datePublished' => $movie->release_date?->toDateString(),
            'duration' => 'PT'.intdiv((int) $movie->duration_minutes, 60).'H'.((int) $movie->duration_minutes % 60).'M',
            'inLanguage' => $movie->language,
            'contentRating' => $movie->certificate_rating,
            'genre' => $movie->genres->pluck('name')->all(),
            'countryOfOrigin' => $movie->country,
            'productionCompany' => $movie->studio ? ['@type' => 'Organization', 'name' => $movie->studio] : null,
            'director' => $movie->creditsFor('director')->map(fn ($credit) => ['@type' => 'Person', 'name' => $credit->person->name])->all() ?: null,
            'actor' => $movie->creditsFor('cast')->map(fn ($credit) => ['@type' => 'Person', 'name' => $credit->person->name])->all() ?: null,
            'aggregateRating' => $rating ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $rating['value'],
                'reviewCount' => $rating['count'],
                'bestRating' => 5,
                'worstRating' => 1,
            ] : null,
            'subjectOf' => collect($shows)->take(10)->map(fn ($show) => self::screening($movie, $show))->values()->all() ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function screening(Movie $movie, object $show): array
    {
        $start = Carbon::parse($show->show_date.' '.$show->show_time);

        return array_filter([
            '@type' => 'ScreeningEvent',
            'name' => $movie->title.' at '.$show->theater_name,
            'startDate' => $start->toIso8601String(),
            'endDate' => $start->copy()->addMinutes((int) $movie->duration_minutes + 20)->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'videoFormat' => $show->format_label ?? null,
            'workPresented' => ['@id' => self::url('movies/'.$movie->slug).'#movie'],
            'location' => [
                '@type' => 'MovieTheater',
                'name' => $show->theater_name,
                'url' => self::url('cinemas/'.$show->theater_slug),
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => $show->theater_address ?? null, 'addressLocality' => $show->city, 'addressCountry' => 'PK'],
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => self::url('movies/'.$movie->slug.'/book/'.$show->show_id),
                'price' => isset($show->from_price) ? number_format((float) $show->from_price, 2, '.', '') : null,
                'priceCurrency' => config('bookmymovie.currency', 'PKR'),
                'availability' => (int) $show->available_seats > 0 ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'validFrom' => now()->startOfDay()->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function theater(Theater $theater): array
    {
        return array_filter([
            '@type' => 'MovieTheater',
            '@id' => self::url('cinemas/'.$theater->slug).'#theater',
            'name' => $theater->name,
            'url' => self::url('cinemas/'.$theater->slug),
            'description' => $theater->description,
            'telephone' => $theater->phone,
            'email' => $theater->email,
            'image' => self::defaultImage(),
            'screenCount' => $theater->screens->count(),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $theater->address,
                'addressLocality' => $theater->city?->name,
                'addressRegion' => $theater->city?->province,
                'addressCountry' => 'PK',
            ],
            'geo' => $theater->latitude ? ['@type' => 'GeoCoordinates', 'latitude' => $theater->latitude, 'longitude' => $theater->longitude] : null,
            'openingHoursSpecification' => $theater->opens_at ? [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'opens' => substr((string) $theater->opens_at, 0, 5),
                'closes' => substr((string) $theater->closes_at, 0, 5),
            ]] : null,
            'amenityFeature' => $theater->amenities->map(fn ($amenity) => ['@type' => 'LocationFeatureSpecification', 'name' => $amenity->name, 'value' => true])->all() ?: null,
        ]);
    }

    /**
     * @param  Collection<int, object>  $faqs
     * @return array<string, mixed>
     */
    public static function faqPage(Collection $faqs): array
    {
        return [
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
            ])->values()->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    public static function graph(array $nodes): string
    {
        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => array_values(array_filter($nodes))],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );
    }

    /**
     * Rewrites a local absolute URL onto the canonical production origin.
     */
    public static function canonicalize(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return self::base().($path === '' ? '/' : $path).($query ? '?'.$query : '');
    }

    public static function slugTitle(string $text): string
    {
        return Str::of($text)->replace(['-', '_'], ' ')->headline()->toString();
    }
}
