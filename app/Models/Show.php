<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Show extends Model
{
    protected $fillable = [
        'movie_id',
        'screen_id',
        'show_date',
        'show_time',
        'status',
        'total_seats',
        'booked_seats',
        'cancellation_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'show_date' => 'date',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ShowSeatPrice::class);
    }

    public function rowPrices(): HasMany
    {
        return $this->hasMany(ShowSeatRowPrice::class);
    }
}
