<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FormSecurity
{
    private const CUSTOM_CAPTCHA_SESSION_PREFIX = 'custom_captcha.';

    private const CUSTOM_CAPTCHA_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

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
     * Verifies the first-party captcha and configured Google reCAPTCHA checks.
     *
     * Google checks are skipped on localhost so local installs can run without
     * Google domain setup even when production keys are configured.
     */
    public static function validateRecaptcha(Request $request, string $action): void
    {
        self::validateCustomCaptcha($request, $action);

        if (self::shouldSkipGoogleRecaptcha($request)) {
            return;
        }

        self::validateRecaptchaV2($request);
        self::validateRecaptchaV3($request, $action);
    }

    /**
     * @return array{code: string, characters: list<array{value: string, class: string}>}
     */
    public static function customCaptchaChallenge(string $action): array
    {
        $code = self::randomCaptchaCode();

        session()->put(self::customCaptchaSessionKey($action), [
            'answer' => self::hashCaptchaAnswer($code),
            'created_at' => now()->timestamp,
        ]);

        $rotations = ['-rotate-6', '-rotate-3', 'rotate-2', 'rotate-3', 'rotate-6'];
        $offsets = ['-translate-y-1', 'translate-y-0', 'translate-y-1'];
        $sizes = ['text-xl', 'text-2xl', 'text-[1.65rem]'];

        return [
            'code' => $code,
            'characters' => array_map(
                fn (string $character): array => [
                    'value' => $character,
                    'class' => $rotations[random_int(0, count($rotations) - 1)]
                        . ' ' . $offsets[random_int(0, count($offsets) - 1)]
                        . ' ' . $sizes[random_int(0, count($sizes) - 1)],
                ],
                str_split($code)
            ),
        ];
    }

    public static function shouldSkipGoogleRecaptcha(Request $request): bool
    {
        if (! (bool) config('services.recaptcha.skip_google_on_localhost', true)) {
            return false;
        }

        $host = strtolower($request->getHost());

        return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
            || str_ends_with($host, '.localhost');
    }

    private static function validateCustomCaptcha(Request $request, string $action): void
    {
        $challenge = $request->session()->pull(self::customCaptchaSessionKey($action));
        $answer = self::normalizeCaptchaAnswer($request->input('custom_captcha_answer', ''));

        if (! is_array($challenge) || ! isset($challenge['answer'], $challenge['created_at'])) {
            throw ValidationException::withMessages([
                'captcha' => 'Security code expired. Refresh the page and try again.',
            ]);
        }

        if ((int) $challenge['created_at'] < now()->subMinutes(15)->timestamp) {
            throw ValidationException::withMessages([
                'captcha' => 'Security code expired. Refresh the page and try again.',
            ]);
        }

        if ($answer === '') {
            throw ValidationException::withMessages([
                'captcha' => 'Please enter the security code.',
            ]);
        }

        if (! hash_equals((string) $challenge['answer'], self::hashCaptchaAnswer($answer))) {
            throw ValidationException::withMessages([
                'captcha' => 'Security code did not match. Please try again.',
            ]);
        }
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

    private static function randomCaptchaCode(): string
    {
        $length = strlen(self::CUSTOM_CAPTCHA_ALPHABET);
        $code = '';

        for ($index = 0; $index < 5; $index++) {
            $code .= self::CUSTOM_CAPTCHA_ALPHABET[random_int(0, $length - 1)];
        }

        return $code;
    }

    private static function normalizeCaptchaAnswer(mixed $answer): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $answer) ?? '');
    }

    private static function hashCaptchaAnswer(string $answer): string
    {
        return hash('sha256', self::normalizeCaptchaAnswer($answer) . '|' . config('app.key'));
    }

    private static function customCaptchaSessionKey(string $action): string
    {
        return self::CUSTOM_CAPTCHA_SESSION_PREFIX . $action;
    }
}
