<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Movie extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'tagline',
        'slug',
        'description',
        'language',
        'studio',
        'country',
        'duration_minutes',
        'certificate_rating',
        'content_advisory',
        'release_date',
        'status',
        'bookings_enabled',
        'poster_image',
        'banner_image',
        'hero_carousel_enabled',
        'hero_sort_order',
        'hero_eyebrow',
        'hero_tagline',
        'hero_image',
        'trailers',
        'meta_title',
        'meta_description',
        'base_price',
        'sale_price',
        'kids_discount_eligible',
        'average_rating',
        'total_reviews',
        'rating_mode',
        'fake_average_rating',
        'fake_total_reviews',
        'created_by',
    ];

    public const CERTIFICATE_LABELS = [
        'G' => 'General audiences',
        'U' => 'Universal',
        'PG' => 'Parental guidance suggested',
        'UA' => 'Parental guidance under 12',
        'PG-13' => 'Parents strongly cautioned under 13',
        'R' => 'Restricted, under 17 with a guardian',
        'A' => 'Adults only',
        'S' => 'Specialised audiences',
    ];

    /** Cached rails and menus that list films; cleared whenever a film changes. */
    public const LISTING_CACHE_KEYS = ['home.rails', 'home.stats', 'nav.movies.cards.v2', 'assistant.context'];

    protected static function booted(): void
    {
        $flush = fn () => array_map(fn (string $key) => \Illuminate\Support\Facades\Cache::forget($key), self::LISTING_CACHE_KEYS);
        static::saved($flush);
        static::deleted($flush);
        static::restored($flush);
    }

    protected function casts(): array
    {
        return [
            'trailers' => 'array',
            'release_date' => 'date',
            'kids_discount_eligible' => 'boolean',
            'hero_carousel_enabled' => 'boolean',
            'hero_sort_order' => 'integer',
            'average_rating' => 'decimal:2',
            'fake_average_rating' => 'decimal:2',
            'fake_total_reviews' => 'integer',
            'base_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'movie_genres');
    }

    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(MovieCredit::class)->orderBy('billing_order');
    }

    /**
     * Adds the three values a movie card needs as correlated subqueries, so a
     * grid of N cards costs one query instead of 3N.
     */
    public function scopeWithCardMetrics(Builder $query): Builder
    {
        $today = now()->toDateString();

        if ($query->getQuery()->columns === null) {
            $query->select('movies.*');
        }

        return $query
            ->addSelect([
                'card_min_sale_price' => ShowSeatPrice::query()
                    ->selectRaw('MIN(show_seat_prices.sale_price)')
                    ->join('shows', 'shows.id', '=', 'show_seat_prices.show_id')
                    ->whereColumn('shows.movie_id', 'movies.id'),
                'card_min_price' => ShowSeatPrice::query()
                    ->selectRaw('MIN(show_seat_prices.price)')
                    ->join('shows', 'shows.id', '=', 'show_seat_prices.show_id')
                    ->whereColumn('shows.movie_id', 'movies.id'),
                'card_first_show_id' => Show::query()
                    ->select('shows.id')
                    ->whereColumn('shows.movie_id', 'movies.id')
                    ->where('shows.status', 'scheduled')
                    ->whereDate('shows.show_date', '>=', $today)
                    // A show that already started today is no longer bookable.
                    ->whereRaw('TIMESTAMP(shows.show_date, shows.show_time) > ?', [now()->toDateTimeString()])
                    ->orderBy('shows.show_date')
                    ->orderBy('shows.show_time')
                    ->limit(1),
            ]);
    }

    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query->whereIn('status', ['now_showing', 'coming_soon']);
    }

    /**
     * Relevance search: FULLTEXT on MySQL/MariaDB, LIKE everywhere else and for
     * terms shorter than InnoDB's minimum token length.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $driver = $query->getConnection()->getDriverName();
        $words = collect(preg_split('/\s+/', $term))
            ->map(fn (string $word) => preg_replace('/[^\pL\pN\']/u', '', $word))
            ->filter(fn (?string $word) => $word !== null && mb_strlen($word) >= 3);

        if (in_array($driver, ['mysql', 'mariadb'], true) && $words->isNotEmpty()) {
            $boolean = $words->map(fn (string $word) => '+'.$word.'*')->implode(' ');

            return $query->where(function (Builder $builder) use ($boolean, $term) {
                $builder->whereFullText(['title', 'tagline', 'description'], $boolean, ['mode' => 'boolean'])
                    ->orWhereHas('genres', fn (Builder $genres) => $genres->where('name', 'like', '%'.addcslashes($term, '\\%_').'%'))
                    ->orWhereHas('credits.person', fn (Builder $people) => $people->where('name', 'like', '%'.addcslashes($term, '\\%_').'%'));
            });
        }

        $like = '%'.addcslashes($term, '\\%_').'%';

        return $query->where(function (Builder $builder) use ($like) {
            $builder->where('title', 'like', $like)
                ->orWhere('tagline', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('genres', fn (Builder $genres) => $genres->where('name', 'like', $like))
                ->orWhereHas('credits.person', fn (Builder $people) => $people->where('name', 'like', $like));
        });
    }

    public function cardPrice(): float
    {
        if (array_key_exists('card_min_sale_price', $this->attributes)) {
            return (float) ($this->attributes['card_min_sale_price']
                ?: $this->attributes['card_min_price']
                ?: $this->sale_price
                ?: $this->base_price
                ?: 0);
        }

        $showIds = $this->shows()->select('id');
        $salePrice = ShowSeatPrice::query()->whereIn('show_id', $showIds)->whereNotNull('sale_price')->min('sale_price');

        return (float) ($salePrice
            ?: ShowSeatPrice::query()->whereIn('show_id', $showIds)->min('price')
            ?: $this->sale_price
            ?: $this->base_price
            ?: 0);
    }

    public function originalCardPrice(): float
    {
        if (array_key_exists('card_min_price', $this->attributes)) {
            return (float) ($this->attributes['card_min_price'] ?: $this->base_price ?: 0);
        }

        return (float) (ShowSeatPrice::query()->whereIn('show_id', $this->shows()->select('id'))->min('price')
            ?: $this->base_price
            ?: 0);
    }

    public function firstScheduledShowId(): ?int
    {
        if (array_key_exists('card_first_show_id', $this->attributes)) {
            return $this->attributes['card_first_show_id'] !== null ? (int) $this->attributes['card_first_show_id'] : null;
        }

        return $this->shows()
            ->where('status', 'scheduled')
            ->whereDate('show_date', '>=', now()->toDateString())
            ->orderBy('show_date')
            ->orderBy('show_time')
            ->value('id');
    }

    public function durationLabel(): string
    {
        $minutes = (int) $this->duration_minutes;

        return intdiv($minutes, 60).'h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'now_showing' => 'Now Showing',
            'coming_soon' => 'Coming Soon',
            default => 'Ended',
        };
    }

    public function certificateLabel(): string
    {
        return self::CERTIFICATE_LABELS[$this->certificate_rating] ?? 'Not yet rated';
    }

    /**
     * @return Collection<int, MovieCredit>
     */
    public function creditsFor(string ...$roles): Collection
    {
        $credits = $this->relationLoaded('credits') ? $this->credits : $this->credits()->with('person')->get();

        return $credits->filter(fn (MovieCredit $credit) => in_array($credit->role, $roles, true))->values();
    }

    public function directorNames(): string
    {
        return $this->creditsFor('director')->map(fn (MovieCredit $credit) => $credit->person?->name)->filter()->join(', ');
    }

    public function toCardArray(?float $price = null): array
    {
        $salePrice = $price ?? $this->cardPrice();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'tagline' => $this->tagline ?: Str::limit((string) $this->description, 110),
            'poster' => $this->poster_image,
            'image' => $this->poster_image,
            'banner' => $this->banner_image,
            'poster_url' => $this->publicMediaUrl($this->poster_image),
            'banner_url' => $this->publicMediaUrl($this->hero_image ?: $this->banner_image),
            'hero_image_url' => $this->publicMediaUrl($this->hero_image ?: $this->banner_image ?: $this->poster_image),
            'hero_eyebrow' => $this->hero_eyebrow ?: $this->statusLabel(),
            'trailers' => $this->trailerList(),
            'genre' => $this->genres->pluck('name')->take(2)->join(' · ') ?: 'Feature',
            'genres' => $this->genres->pluck('name')->all(),
            'language' => $this->language,
            'duration' => $this->durationLabel(),
            'duration_minutes' => (int) $this->duration_minutes,
            'rating' => $this->displayRating(),
            'reviews' => $this->displayReviewCount(),
            'certificate' => $this->certificate_rating ?: 'NR',
            'status' => $this->statusLabel(),
            'status_key' => $this->status,
            'release_date' => $this->release_date?->format('j M Y'),
            'release_year' => $this->release_date?->format('Y'),
            'price' => $salePrice,
            'original_price' => $this->originalCardPrice(),
            'sale_price' => $salePrice,
            'first_show_id' => $this->firstScheduledShowId(),
            'palette' => $this->posterPalette(),
        ];
    }

    /**
     * @return list<array{src: string, webm: ?string, label: string, poster: ?string}>
     */
    public function trailerList(): array
    {
        return collect((array) $this->trailers)
            ->filter(fn ($trailer) => is_array($trailer) && filled($trailer['src'] ?? null))
            ->map(fn (array $trailer) => [
                'src' => (string) $this->publicMediaUrl($trailer['src']),
                'webm' => $this->publicMediaUrl($trailer['webm'] ?? null),
                'label' => (string) ($trailer['label'] ?? 'Trailer'),
                'poster' => $this->publicMediaUrl($trailer['poster'] ?? null),
            ])
            ->values()
            ->all();
    }

    public function displayRating(): float
    {
        if ($this->rating_mode === 'fake' && $this->fake_average_rating !== null) {
            return (float) $this->fake_average_rating;
        }

        return (float) $this->average_rating;
    }

    public function displayReviewCount(): int
    {
        if ($this->rating_mode === 'fake' && $this->fake_total_reviews !== null) {
            return (int) $this->fake_total_reviews;
        }

        return (int) $this->total_reviews;
    }

    /**
     * Rating that is safe to publish as schema.org AggregateRating: only real,
     * approved reviews count, never the admin "display" override.
     *
     * @return array{value: float, count: int}|null
     */
    public function verifiedRating(): ?array
    {
        $count = (int) $this->total_reviews;

        return $count > 0 ? ['value' => round((float) $this->average_rating, 1), 'count' => $count] : null;
    }

    /**
     * Deterministic two-tone palette per title, used by the typographic
     * poster when no artwork has been uploaded.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function posterPalette(): array
    {
        $palettes = [
            ['#1d2b3a', '#c8a15a', '#f3ead8'],
            ['#3b1f1f', '#e0823d', '#f6e9dc'],
            ['#14261f', '#9fc28a', '#eef3e6'],
            ['#241b33', '#c49bdb', '#f1e9f6'],
            ['#2e2616', '#e8c35a', '#f7f0da'],
            ['#1a1f2e', '#7aa7e0', '#e6eefa'],
            ['#301620', '#e46a7e', '#f9e6ea'],
            ['#1f2a2a', '#62c1b4', '#e3f4f1'],
            ['#2b2320', '#d99a6c', '#f5ebe3'],
            ['#161616', '#e9e2d0', '#f4f1ea'],
        ];

        return $palettes[crc32((string) $this->slug) % count($palettes)];
    }

    public function ogImageUrl(): string
    {
        $path = 'images/og/movies/'.$this->slug.'.png';

        return is_file(public_path($path)) ? asset($path) : asset('images/og/default.png');
    }

    public function publicMediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (Str::startsWith($path, ['storage/', '/storage/', 'images/', '/images/', 'videos/', '/videos/'])) {
            return asset(ltrim($path, '/'));
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * Recalculates the cached rating columns from approved reviews. The review
     * triggers do the same in MySQL; this covers hosts without trigger rights.
     */
    public function refreshRating(): void
    {
        $aggregate = DB::table('reviews')
            ->where('movie_id', $this->id)
            ->where('is_approved', true)
            ->selectRaw('COALESCE(ROUND(AVG(rating), 2), 0) AS average, COUNT(*) AS total')
            ->first();

        $this->forceFill([
            'average_rating' => $aggregate->average,
            'total_reviews' => $aggregate->total,
        ])->saveQuietly();
    }
}
