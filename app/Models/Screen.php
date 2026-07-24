<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Screen extends Model
{
    protected $fillable = ['theater_id', 'screen_name', 'total_seats', 'is_active'];

    public function theater(): BelongsTo
    {
        return $this->belongsTo(Theater::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }
}
