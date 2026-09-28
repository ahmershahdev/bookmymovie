<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concession extends Model
{
    protected $fillable = ['slug', 'name', 'name_ur', 'description', 'category', 'price', 'stock', 'low_stock_at', 'icon', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'stock' => 'integer', 'low_stock_at' => 'integer', 'is_active' => 'boolean'];
    }
}
