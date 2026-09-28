<?php

namespace App\Http\Middleware;

use App\Support\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Application-level DoS and abuse shield, applied to every web request.
 *
 *  1. Banned IPs are rejected before any session, database or view work.
 *  2. Oversized request bodies are refused (Slowloris-style body floods).
 *  3. Well-known vulnerability-scanner paths earn a strike and a 404.
 *  4. Two sliding windows per IP: a 10-second burst limit and a per-minute
 *     limit. Every breach is a strike; enough strikes and the IP is banned
 *     for `shield.ban_minutes`.
 *
 * This complements, and does not replace, network-edge DDoS protection.
 */
class ShieldAgainstAbuse
{
    private const SCANNER_PATTERNS = [
        '#^/?(wp-admin|wp-login\.php|wp-content|wp-includes|xmlrpc\.php)#i',
        '#^/?(phpmyadmin|pma|myadmin|adminer)(/|\.php|$)#i',
        '#(^|/)\.(env|git|svn|hg|DS_Store|aws|ssh)(/|$)#i',
        '#^/?(vendor/phpunit|cgi-bin|boaform|HNAP1|actuator|solr|console)(/|$)#i',
        '#\.(bak|old|sql|swp|ini|log)$#i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $config = config('bookmymovie.shield');
        $ip = (string) $request->ip();

        if (! ($config['enabled'] ?? true) || in_array($ip, $config['allowlist'] ?? [], true)) {
            return $next($request);
        }

        if ($bannedUntil = Cache::get($this->banKey($ip))) {
            return $this->tooManyRequests(max(1, (int) $bannedUntil - now()->timestamp));
        }

        if ($this->bodyTooLarge($request, (int) ($config['max_body_kb'] ?? 6144))) {
            return response('Request body too large.', 413);
        }

        if ($this->looksLikeScanner($request)) {
            $this->strike($request, SecurityLog::SCANNER_PROBE, $config);

            abort(404);
        }

        $burstKey = 'shield:burst:'.$ip;
        $minuteKey = 'shield:minute:'.$ip;

        if (RateLimiter::tooManyAttempts($burstKey, (int) $config['burst_per_10_seconds'])
            || RateLimiter::tooManyAttempts($minuteKey, (int) $config['requests_per_minute'])) {
            $this->strike($request, SecurityLog::RATE_LIMITED, $config);

            return $this->tooManyRequests(max(RateLimiter::availableIn($burstKey), RateLimiter::availableIn($minuteKey), 1));
        }

        RateLimiter::hit($burstKey, 10);
        RateLimiter::hit($minuteKey, 60);

        return $next($request);
    }

    private function strike(Request $request, string $reason, array $config): void
    {
        $ip = (string) $request->ip();
        $strikesKey = 'shield:strikes:'.$ip;

        // add() is atomic and a no-op when the key exists; some stores return
        // false from increment() on a missing key. Strikes decay after an hour.
        Cache::add($strikesKey, 0, now()->addHour());
        $strikes = (int) Cache::increment($strikesKey);

        if ($strikes === 1 || $strikes % 10 === 0) {
            SecurityLog::record($reason, $request, ['strikes' => $strikes]);
        }

        if ($strikes >= (int) $config['strikes_before_ban']) {
            $minutes = (int) $config['ban_minutes'];
            Cache::put($this->banKey($ip), now()->addMinutes($minutes)->timestamp, now()->addMinutes($minutes));
            Cache::forget($strikesKey);
            SecurityLog::record(SecurityLog::IP_BANNED, $request, ['minutes' => $minutes, 'reason' => $reason]);
        }
    }

    private function looksLikeScanner(Request $request): bool
    {
        $path = '/'.ltrim($request->path(), '/');

        foreach (self::SCANNER_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private function bodyTooLarge(Request $request, int $maxKilobytes): bool
    {
        $length = (int) $request->server('CONTENT_LENGTH', 0);

        return $length > $maxKilobytes * 1024;
    }

    private function banKey(string $ip): string
    {
        return 'shield:ban:'.$ip;
    }

    private function tooManyRequests(int $retryAfter): Response
    {
        return response()
            ->view('errors.429', ['retryAfter' => $retryAfter], 429)
            ->header('Retry-After', (string) $retryAfter)
            ->header('Cache-Control', 'no-store');
    }
}
