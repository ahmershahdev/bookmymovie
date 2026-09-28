<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Append-only record of meaningful changes: sign-ins, profile and password
 * changes, bookings, payments and every admin action. Secrets are never
 * stored; only field names for sensitive updates.
 */
class AuditLog
{
    private const REDACT = ['password', 'password_confirmation', 'current_password', 'secret_key', 'invite_code', 'custom_captcha_answer', 'g-recaptcha-response', 'recaptcha_v3_token', '_token'];

    /**
     * @param  array<string, mixed>  $changes
     */
    public static function record(string $action, ?Model $subject = null, array $changes = [], ?Request $request = null, ?string $actorType = null, ?int $actorId = null): void
    {
        $request ??= request();

        if ($actorType === null) {
            if ($request->hasSession() && $request->session()->has('admin_id')) {
                [$actorType, $actorId] = ['admin', (int) $request->session()->get('admin_id')];
            } elseif ($request->user()) {
                [$actorType, $actorId] = ['user', (int) $request->user()->id];
            } else {
                $actorType = 'guest';
            }
        }

        foreach (self::REDACT as $key) {
            if (array_key_exists($key, $changes)) {
                $changes[$key] = '[redacted]';
            }
        }

        try {
            DB::table('audit_logs')->insert([
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'changes' => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // Auditing must never break the action being audited.
            Log::warning('Audit log write failed', ['action' => $action, 'error' => $exception->getMessage()]);
        }
    }
}
