<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'trailer_url',
        'kids_discount_eligible',
        'average_rating',
        'total_reviews',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'kids_discount_eligible' => 'boolean',
            'average_rating' => 'decimal:2',
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
        return (float) ShowSeatPrice::query()
            ->whereIn('show_id', $this->shows()->select('id'))
            ->min('price') ?: 0;
    }

    public function toCardArray(?float $price = null): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'genre' => $this->genres->pluck('name')->join(' / ') ?: 'Cinema',
            'language' => $this->language,
            'duration' => intdiv((int) $this->duration_minutes, 60).'h '.((int) $this->duration_minutes % 60).'m',
            'rating' => (float) $this->average_rating ?: 4.0,
            'certificate' => $this->certificate_rating ?: 'UA',
            'status' => str($this->status)->replace('_', ' ')->headline()->toString(),
            'price' => $price ?? $this->cardPrice(),
            'gradient' => 'from-red-950 via-gray-950 to-black',
            'tagline' => str($this->description ?: 'Premium cinema experience.')->limit(90)->toString(),
        ];
    }
}
