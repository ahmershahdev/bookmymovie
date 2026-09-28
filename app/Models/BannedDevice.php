<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/** A browser an admin has blocked from the whole site (see App\Support\DeviceIdentity). */
class BannedDevice extends Model
{
    public const CACHE_KEY = 'banned_devices.active';

    protected $fillable = ['device_hash', 'reason', 'banned_by', 'user_id', 'label', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn (Builder $inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** @return array<string, true> */
    public static function activeSet(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, fn () => self::query()->active()->pluck('device_hash')->mapWithKeys(fn ($hash) => [$hash => true])->all());
    }

    public static function isBanned(?string $hash): bool
    {
        return $hash !== null && isset(self::activeSet()[$hash]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
