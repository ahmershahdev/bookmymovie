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
        'addons_total',
        'gift_card_id',
        'gift_card_amount',
        'points_redeemed',
        'points_earned',
        'payment_method',
        'payment_status',
        'booking_status',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    /**
     * Allowed booking_status moves. Terminal states have no way out, so a
     * cancelled booking can never be cancelled (and refunded) twice.
     */
    public const STATUS_TRANSITIONS = [
        'confirmed' => ['completed', 'cancelled', 'no_show'],
        'completed' => [],
        'no_show' => [],
        'cancelled' => [],
    ];

    public function canMoveTo(string $status): bool
    {
        return in_array($status, self::STATUS_TRANSITIONS[$this->booking_status] ?? [], true);
    }

    /**
     * Status lock (compare-and-set): moves the row from the status this model
     * last saw to $status in one guarded UPDATE. If another request changed
     * the status in between, no row matches and nothing is written.
     *
     * @param  array<string, mixed>  $extra  columns written in the same UPDATE
     *
     * @throws \App\Exceptions\StaleStatusException
     */
    public function claimStatus(string $status, array $extra = []): void
    {
        $from = (string) $this->booking_status;

        if (! $this->canMoveTo($status)) {
            throw new \App\Exceptions\StaleStatusException("Booking {$this->booking_number} cannot move from {$from} to {$status}.");
        }

        $claimed = static::query()->whereKey($this->getKey())->where('booking_status', $from)
            ->update(['booking_status' => $status, 'updated_at' => now(), ...$extra]);

        if ($claimed !== 1) {
            throw new \App\Exceptions\StaleStatusException("Booking {$this->booking_number} changed while it was being updated.");
        }

        $this->forceFill(['booking_status' => $status, ...$extra])->syncOriginal();
    }

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

    public function concessions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Concession::class, 'booking_concessions')->withPivot(['quantity', 'unit_price'])->withTimestamps();
    }

    public function giftCard(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
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
