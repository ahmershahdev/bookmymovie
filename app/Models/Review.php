<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'movie_id',
        'booking_id',
        'rating',
        'title',
        'review_text',
        'contains_spoilers',
        'helpful_count',
        'is_approved',
        'is_flagged',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'is_flagged' => 'boolean',
            'contains_spoilers' => 'boolean',
            'helpful_count' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Rows cascade in the database; the files have to go too.
        static::deleting(function (Review $review) {
            foreach ($review->photos as $photo) {
                \App\Support\ReviewPhotos::delete($photo->path);
            }
        });
    }

    public function photos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReviewPhoto::class)->orderBy('position');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * "Verified booking": the review is tied to one of the reviewer's own
     * bookings for this film, and that show has already started. The link
     * is only ever set by MovieController::storeReview (or the seeder, which
     * creates the matching past booking), never from request input.
     */
    public function isVerified(): bool
    {
        return $this->booking_id !== null;
    }
}
