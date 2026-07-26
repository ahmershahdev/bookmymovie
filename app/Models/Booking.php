<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    const CREATED_AT = 'booked_at';

    protected $fillable = [
        'booking_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'show_id',
        'coupon_id',
        'seat_count',
        'adult_count',
        'kids_count',
        'subtotal',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'booking_status',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }
}
