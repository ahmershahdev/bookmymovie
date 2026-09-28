<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['name', 'slug', 'province', 'country', 'timezone'];

    public function theaters(): HasMany
    {
        return $this->hasMany(Theater::class);
    }
}
