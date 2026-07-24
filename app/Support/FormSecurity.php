<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FormSecurity
{
    /**
     * Common disposable domains used for spam signups and support messages.
     *
     * @var list<string>
     */
    private const DISPOSABLE_DOMAINS = [
        '10minutemail.com',
        '20minutemail.com',
        'guerrillamail.com',
        'mailinator.com',
        'tempmail.com',
        'temp-mail.org',
        'throwawaymail.com',
        'trashmail.com',
        'yopmail.com',
        'dispostable.com',
        'sharklasers.com',
        'getnada.com',
        'maildrop.cc',
        'moakt.com',
        'mintemail.com',
    ];

    public static function disposableEmailRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $domain = strtolower((string) str($value)->afterLast('@'));

            if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
                $fail('Disposable or temporary email addresses are not allowed.');
            }
        };
    }

    /**
     * Verifies configured Google reCAPTCHA v2 and v3 checks.
     *
     * If no secret keys are present, validation is skipped so local installs and
     * CI can run without Google credentials.
     */
    public static function validateRecaptcha(Request $request, string $action): void
    {
        self::validateRecaptchaV2($request);
        self::validateRecaptchaV3($request, $action);
    }

    private static function validateRecaptchaV2(Request $request): void
    {
        $secret = config('services.recaptcha.v2_secret_key');

        if (! $secret) {
            return;
        }

        $token = (string) $request->input('g-recaptcha-response');

        if ($token === '') {
            throw ValidationException::withMessages([
                'captcha' => 'Please complete the visible captcha check.',
            ]);
        }

        $payload = self::verifyWithGoogle($secret, $token, $request);

        if (! ($payload['success'] ?? false)) {
            throw ValidationException::withMessages([
                'captcha' => 'Captcha verification failed. Please try again.',
            ]);
        }
    }

    private static function validateRecaptchaV3(Request $request, string $action): void
    {
        $secret = config('services.recaptcha.v3_secret_key');

        if (! $secret) {
            return;
        }

        $token = (string) $request->input('recaptcha_v3_token');

        if ($token === '') {
            throw ValidationException::withMessages([
                'captcha' => 'Background captcha could not start. Refresh the page and try again.',
            ]);
        }

        $payload = self::verifyWithGoogle($secret, $token, $request);
        $minimumScore = (float) config('services.recaptcha.v3_min_score', 0.5);

        if (
            ! ($payload['success'] ?? false)
            || ($payload['action'] ?? null) !== $action
            || (float) ($payload['score'] ?? 0) < $minimumScore
        ) {
            throw ValidationException::withMessages([
                'captcha' => 'Captcha risk check failed. Please try again.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function verifyWithGoogle(string $secret, string $token, Request $request): array
    {
        try {
            return Http::asForm()
                ->timeout(8)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ])
                ->json() ?? [];
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'captcha' => 'Captcha service is unavailable. Please try again in a moment.',
            ]);
        }
    }
}
