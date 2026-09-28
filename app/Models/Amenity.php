<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'description'];

    public function theaters(): BelongsToMany
    {
        return $this->belongsToMany(Theater::class, 'theater_amenity');
    }
}
