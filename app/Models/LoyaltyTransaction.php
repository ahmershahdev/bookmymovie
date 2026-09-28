<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'booking_id', 'points', 'reason'];
}
