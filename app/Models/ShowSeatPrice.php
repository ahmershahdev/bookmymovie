<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowSeatPrice extends Model
{
    protected $fillable = ['show_id', 'seat_category_id', 'price', 'sale_price', 'kids_price', 'kids_sale_price'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'kids_price' => 'decimal:2',
            'kids_sale_price' => 'decimal:2',
        ];
    }
}
