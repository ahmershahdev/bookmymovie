<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'payment_method',
        'amount',
        'status',
        'transaction_reference',
        'notes',
        'paid_at',
        'refunded_at',
        'refund_reason',
    ];
}
