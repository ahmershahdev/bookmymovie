<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['booking_id', 'event', 'from_status', 'to_status', 'note', 'actor_type', 'actor_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
