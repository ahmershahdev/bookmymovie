<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Movie extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'language',
        'duration_minutes',
        'certificate_rating',
        'release_date',
        'status',
        'poster_image',
        'banner_image',
        'hero_carousel_enabled',
        'hero_sort_order',
        'hero_eyebrow',
        'hero_tagline',
        'hero_image',
        'trailer_url',
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

    protected function casts(): array
    {
        return [
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

    public function cardPrice(): float
    {
        $showSalePrice = ShowSeatPrice::query()
            ->whereIn('show_id', $this->shows()->select('id'))
            ->whereNotNull('sale_price')
            ->min('sale_price');

        if ($showSalePrice) {
            return (float) $showSalePrice;
        }

        $showPrice = ShowSeatPrice::query()
            ->whereIn('show_id', $this->shows()->select('id'))
            ->min('price');

        return (float) ($showPrice ?: $this->sale_price ?: $this->base_price ?: 0);
    }

    public function originalCardPrice(): float
    {
        $showPrice = ShowSeatPrice::query()
            ->whereIn('show_id', $this->shows()->select('id'))
            ->min('price');

        return (float) ($showPrice ?: $this->base_price ?: 0);
    }

    public function firstScheduledShowId(): ?int
    {
        return $this->shows()
            ->where('status', 'scheduled')
            ->whereDate('show_date', '>=', now()->toDateString())
            ->orderBy('show_date')
            ->orderBy('show_time')
            ->value('id');
    }

    public function toCardArray(?float $price = null): array
    {
        $salePrice = $price ?? $this->cardPrice();
        $originalPrice = $this->originalCardPrice();
        $rating = $this->displayRating();
        $reviews = $this->displayReviewCount();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'poster' => $this->poster_image,
            'image' => $this->poster_image,
            'banner' => $this->banner_image,
            'poster_url' => $this->publicMediaUrl($this->poster_image),
            'banner_url' => $this->publicMediaUrl($this->banner_image),
            'hero_image_url' => $this->publicMediaUrl($this->hero_image ?: $this->banner_image ?: $this->poster_image),
            'hero_eyebrow' => $this->hero_eyebrow ?: str($this->status)->replace('_', ' ')->headline()->toString(),
            'genre' => $this->genres->pluck('name')->join(' / ') ?: 'Cinema',
            'language' => $this->language,
            'duration' => intdiv((int) $this->duration_minutes, 60).'h '.((int) $this->duration_minutes % 60).'m',
            'rating' => $rating,
            'reviews' => $reviews,
            'certificate' => $this->certificate_rating ?: 'UA',
            'status' => str($this->status)->replace('_', ' ')->headline()->toString(),
            'price' => $salePrice,
            'original_price' => $originalPrice,
            'sale_price' => $salePrice,
            'first_show_id' => $this->firstScheduledShowId(),
            'gradient' => 'from-red-950 via-gray-950 to-black',
            'tagline' => str($this->hero_tagline ?: $this->description ?: 'Premium cinema experience.')->limit(120)->toString(),
        ];
    }

    public function displayRating(): float
    {
        if ($this->rating_mode === 'fake' && $this->fake_average_rating !== null) {
            return (float) $this->fake_average_rating;
        }

        return (float) $this->average_rating ?: 4.0;
    }

    public function displayReviewCount(): int
    {
        if ($this->rating_mode === 'fake' && $this->fake_total_reviews !== null) {
            return (int) $this->fake_total_reviews;
        }

        return (int) $this->total_reviews;
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

        if (Str::startsWith($path, ['storage/', '/storage/'])) {
            return asset(ltrim($path, '/'));
        }

        if (Str::startsWith($path, ['images/', '/images/'])) {
            return asset(ltrim($path, '/'));
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
