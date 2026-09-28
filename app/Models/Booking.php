<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    const CREATED_AT = 'booked_at';

    protected $fillable = [
        'booking_number',
        'idempotency_key',
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

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(BookingEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function showStartsAt(): ?Carbon
    {
        if (! $this->show) {
            return null;
        }

        return Carbon::parse($this->show->show_date->toDateString().' '.$this->show->show_time);
    }

    /**
     * Unpaid bookings may be cancelled by the customer until the cutoff.
     */
    public function isCancellableByCustomer(): bool
    {
        $startsAt = $this->showStartsAt();

        return $this->booking_status === 'confirmed'
            && $this->payment_status === 'pending'
            && $startsAt !== null
            && now()->addMinutes((int) config('bookmymovie.booking.cancellation_cutoff_minutes', 120))->lt($startsAt);
    }
}
