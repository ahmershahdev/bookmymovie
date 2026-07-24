<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowSeatPrice extends Model
{
    protected $fillable = ['show_id', 'seat_category_id', 'price', 'kids_price'];
}
