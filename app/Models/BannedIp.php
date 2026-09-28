<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/** An IP address an admin has blocked from the whole site, optionally until a date. */
class BannedIp extends Model
{
    public const CACHE_KEY = 'banned_ips.active';

    protected $fillable = ['ip_address', 'reason', 'banned_by', 'user_id', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Every request checks the list, so it is cached and refreshed on change.
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
        return Cache::remember(self::CACHE_KEY, 300, fn () => self::query()->active()->pluck('ip_address')->mapWithKeys(fn ($ip) => [$ip => true])->all());
    }

    public static function isBanned(?string $ip): bool
    {
        return $ip !== null && isset(self::activeSet()[$ip]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'banned_by');
    }
}
