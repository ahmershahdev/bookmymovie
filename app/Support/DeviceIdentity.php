<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A stable, private id per browser, used to make bans stick.
 *
 * The id is 40 random characters in a long-lived cookie. Laravel encrypts
 * and signs cookies, so it cannot be read or forged; only its SHA-256 hash
 * is ever stored. Clearing cookies gets a new id, which is why a ban also
 * covers every IP the member used: the two together close the easy doors
 * (new account, new network, private window) without fingerprinting.
 */
class DeviceIdentity
{
    public const COOKIE = 'bmm_device';

    private const LIFETIME_MINUTES = 60 * 24 * 730;

    /** Reads the id, issuing one (queued on the response) the first time. */
    public static function id(Request $request): string
    {
        if ($request->attributes->has(self::COOKIE)) {
            return (string) $request->attributes->get(self::COOKIE);
        }

        $id = (string) $request->cookie(self::COOKIE);

        if (! preg_match('/^[A-Za-z0-9]{40}$/', $id)) {
            $id = Str::random(40);
            Cookie::queue(cookie(self::COOKIE, $id, self::LIFETIME_MINUTES, '/', null, null, true, false, 'lax'));
        }

        $request->attributes->set(self::COOKIE, $id);

        return $id;
    }

    public static function hash(Request $request): string
    {
        return hash('sha256', self::id($request));
    }

    /**
     * Records that this member used this browser from this IP. Called on
     * sign-in and, at most every ten minutes, on signed-in requests.
     */
    public static function remember(Request $request, User $user, bool $force = false): void
    {
        $hash = self::hash($request);
        $throttle = 'device_seen:'.$user->id.':'.substr($hash, 0, 16);

        if (! $force && ! Cache::add($throttle, 1, now()->addMinutes(10))) {
            return;
        }

        // Upsert: a race between two tabs cannot create a duplicate row.
        DB::table('user_devices')->upsert([[
            'user_id' => $user->id,
            'device_hash' => $hash,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]], ['user_id', 'device_hash'], ['ip_address', 'user_agent', 'last_seen_at']);
    }

    /** A short, readable browser label for the admin screens. */
    public static function label(?string $agent): string
    {
        $agent = (string) $agent;
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'unknown OS',
        };

        return $browser.' on '.$os;
    }
}
