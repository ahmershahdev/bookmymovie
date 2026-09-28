<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concession extends Model
{
    protected $fillable = ['slug', 'name', 'name_ur', 'description', 'category', 'price', 'icon', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
