<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seat extends Model
{
    public $timestamps = false;

    protected $fillable = ['screen_id', 'seat_category_id', 'row_label', 'seat_number', 'is_active'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SeatCategory::class, 'seat_category_id');
    }
}
