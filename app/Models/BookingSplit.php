<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSplit extends Model
{
    protected $fillable = ['booking_id', 'token', 'label', 'amount', 'status', 'is_host', 'payer_name', 'payment_method', 'payment_reference', 'paid_at'];

    protected $hidden = ['token', 'payment_reference'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_host' => 'boolean', 'paid_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
