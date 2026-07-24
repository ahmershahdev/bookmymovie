<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'is_public'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function publicMap(): array
    {
        return self::query()
            ->where('is_public', true)
            ->pluck('value', 'key')
            ->map(fn ($value) => (string) $value)
            ->all();
    }
}
