<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSeat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'seat_id',
        'show_id',
        'seat_category_id',
        'ticket_type',
        'price_paid',
        'ticket_number',
        'created_at',
    ];

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }
}
