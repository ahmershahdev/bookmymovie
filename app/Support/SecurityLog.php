<?php

namespace App\Support;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Append-only record of security-relevant events (failed sign-ins, bans,
 * captcha failures, scanner probes). Writing must never break the request,
 * so failures fall back to the application log.
 */
class SecurityLog
{
    public const FAILED_LOGIN = 'failed_login';

    public const LOCKOUT = 'lockout';

    public const CAPTCHA_FAILED = 'captcha_failed';

    public const BOT_TRAP = 'bot_trap';

    public const RATE_LIMITED = 'rate_limited';

    public const IP_BANNED = 'ip_banned';

    public const SCANNER_PROBE = 'scanner_probe';

    public const PASSWORD_RESET = 'password_reset';

    public const ADMIN_LOGIN = 'admin_login';

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function record(string $type, Request $request, array $meta = []): void
    {
        try {
            SecurityEvent::create([
                'type' => $type,
                'ip_address' => (string) $request->ip(),
                'user_id' => $request->user()?->getKey(),
                'method' => $request->method(),
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('security_event_write_failed', ['type' => $type, 'ip' => $request->ip(), 'error' => $exception->getMessage()]);
        }
    }
}
