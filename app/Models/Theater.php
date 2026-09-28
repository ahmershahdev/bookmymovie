<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theater extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'address',
        'city_id',
        'pincode',
        'phone',
        'email',
        'description',
        'latitude',
        'longitude',
        'opens_at',
        'closes_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function screens(): HasMany
    {
        return $this->hasMany(Screen::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'theater_amenity');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
