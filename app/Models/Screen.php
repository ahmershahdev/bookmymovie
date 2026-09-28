<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Screen extends Model
{
    protected $fillable = ['theater_id', 'screen_name', 'format', 'sound_system', 'is_wheelchair_accessible', 'total_seats', 'is_active'];

    public const FORMAT_LABELS = [
        'standard' => 'Standard 2D',
        'imax' => 'IMAX with Laser',
        'dolby_cinema' => 'Dolby Cinema',
        '4dx' => '4DX Motion',
        'screenx' => 'ScreenX 270°',
        'recliner' => 'Recliner Lounge',
    ];

    public function formatLabel(): string
    {
        return self::FORMAT_LABELS[$this->format] ?? 'Standard 2D';
    }

    public function theater(): BelongsTo
    {
        return $this->belongsTo(Theater::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }
}
