<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'max_uses',
        'used_count',
        'max_uses_per_user',
        'valid_from',
        'valid_until',
        'is_active',
        'created_by',
    ];
}
