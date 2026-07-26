<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'canonical_path',
        'hero_label',
        'excerpt',
        'body',
        'sections',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
