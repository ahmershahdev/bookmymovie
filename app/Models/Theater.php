<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theater extends Model
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'email',
        'is_active',
        'created_by',
    ];

    public function screens(): HasMany
    {
        return $this->hasMany(Screen::class);
    }
}
