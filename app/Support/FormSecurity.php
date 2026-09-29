<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Layered bot protection for public forms, cheapest check first:
 *
 *  1. Honeypot  - a hidden field real people never fill in.
 *  2. Fill time - a form submitted faster than a human can type is rejected.
 *  3. Image code - a server-rendered, distorted PNG; the answer never appears
 *     in the DOM, only its salted hash lives in the session (single use).
 *  4. Google reCAPTCHA, when keys are set. Only one Google check is ever
 *     shown: with a v3 key, v3 runs invisibly on every submit and the v2
 *     checkbox appears only as a step-up after v3 returns a low score (or
 *     fails to load). With only a v2 key, the checkbox is always shown.
 */
class FormSecurity
{
    public const HONEYPOT_FIELD = 'website_url';

    private const CUSTOM_CAPTCHA_SESSION_PREFIX = 'custom_captcha.';

    private const CUSTOM_CAPTCHA_ALPHABET = 'ABCDEFGHJKLMNPRSTUVWXYZ2346789';

    private const CAPTCHA_LENGTH = 5;

    private const CAPTCHA_TTL_MINUTES = 15;

    /**
     * Common disposable domains used for spam sign-ups and support messages.
     *
     * @var list<string>
     */
    private const DISPOSABLE_DOMAINS = [
        '10minutemail.com', '20minutemail.com', 'guerrillamail.com', 'mailinator.com', 'tempmail.com',
        'temp-mail.org', 'throwawaymail.com', 'trashmail.com', 'yopmail.com', 'dispostable.com',
        'sharklasers.com', 'getnada.com', 'maildrop.cc', 'moakt.com', 'mintemail.com', 'fakeinbox.com',
        'emailondeck.com', 'mohmal.com', 'tempail.com', 'burnermail.io',
    ];

    public static function disposableEmailRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $domain = strtolower((string) str((string) $value)->afterLast('@'));

            if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
                $fail('Please use a permanent email address. Temporary inboxes are not accepted.');
            }
        };
    }

    /**
     * Runs every layer for the given form action. Throws a ValidationException
     * with a `captcha` error on failure.
     */
    public static function validateRecaptcha(Request $request, string $action): void
    {
        $challenge = $request->session()->pull(self::customCaptchaSessionKey($action));

        self::validateHoneypot($request, $action);
        self::validateFillTime($request, $action, $challenge);
        self::validateCustomCaptcha($request, $action, $challenge);

        if (self::shouldSkipGoogleRecaptcha($request)) {
            return;
        }

        if (! self::usesV3()) {
            self::validateRecaptchaV2($request, $action);

            return;
        }

        // Step-up: this visitor was shown the checkbox, so the checkbox decides.
        if ($request->session()->get(self::stepUpKey($action)) && config('services.recaptcha.v2_secret_key')) {
            self::validateRecaptchaV2($request, $action);
            $request->session()->forget(self::stepUpKey($action));

            return;
        }

        self::validateRecaptchaV3($request, $action);
    }

    private static function usesV3(): bool
    {
        return (bool) (config('services.recaptcha.v3_site_key') && config('services.recaptcha.v3_secret_key'));
    }

    private static function stepUpKey(string $action): string
    {
        return 'recaptcha_step_up.'.$action;
    }

    /** v3 was unsure: ask for the checkbox next time, if a v2 key exists. */
    private static function stepUp(Request $request, string $action, string $message): never
    {
        if (config('services.recaptcha.v2_site_key') && config('services.recaptcha.v2_secret_key')) {
            $request->session()->put(self::stepUpKey($action), true);
            self::fail('One more check: please tick “I’m not a robot” and send again.');
        }

        self::fail($message);
    }

    /**
     * Issues a fresh single-use challenge for a form and returns it as an
     * inline PNG data URI.
     *
     * @return array{image: ?string, length: int}
     */
    public static function customCaptchaChallenge(string $action): array
    {
        $code = self::randomCaptchaCode();

        session()->put(self::customCaptchaSessionKey($action), [
            'answer' => self::hashCaptchaAnswer($code),
            'created_at' => now()->timestamp,
        ]);

        return [
            'image' => self::renderCaptchaImage($code),
            'length' => self::CAPTCHA_LENGTH,
        ];
    }

    /**
     * Everything the React captcha block needs: a fresh image challenge plus
     * the public reCAPTCHA keys when Google checks are active for this host.
     *
     * @return array{action: string, image: ?string, length: int, v2_site_key: ?string, v3_site_key: ?string}
     */
    public static function forPage(string $action): array
    {
        $skipGoogle = self::shouldSkipGoogleRecaptcha(request());
        $v3 = ! $skipGoogle && self::usesV3();
        // With v3 on, the checkbox only appears for a step-up (see stepUp()).
        $showV2 = ! $skipGoogle && (! $v3 || session()->get(self::stepUpKey($action)));

        return [
            'action' => $action,
            ...self::customCaptchaChallenge($action),
            'v2_site_key' => $showV2 ? (config('services.recaptcha.v2_site_key') ?: null) : null,
            'v3_site_key' => $v3 ? config('services.recaptcha.v3_site_key') : null,
        ];
    }

    public static function shouldSkipGoogleRecaptcha(Request $request): bool
    {
        if (! (bool) config('services.recaptcha.skip_google_on_localhost', true)) {
            return false;
        }

        $host = strtolower($request->getHost());

        return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test');
    }

    private static function validateHoneypot(Request $request, string $action): void
    {
        if (trim((string) $request->input(self::HONEYPOT_FIELD, '')) !== '') {
            SecurityLog::record(SecurityLog::BOT_TRAP, $request, ['action' => $action, 'trap' => 'honeypot']);

            // Deliberately vague: do not teach the bot which check it tripped.
            self::fail('We could not verify this submission. Please refresh the page and try again.');
        }
    }

    private static function validateFillTime(Request $request, string $action, mixed $challenge): void
    {
        $minimum = (int) config('bookmymovie.forms.minimum_fill_seconds', 3);

        if (is_array($challenge) && isset($challenge['created_at']) && now()->timestamp - (int) $challenge['created_at'] < $minimum) {
            SecurityLog::record(SecurityLog::BOT_TRAP, $request, ['action' => $action, 'trap' => 'fill_time']);

            self::fail('That was a little too quick. Please take a moment and submit the form again.');
        }
    }

    private static function validateCustomCaptcha(Request $request, string $action, mixed $challenge): void
    {
        $answer = self::normalizeCaptchaAnswer($request->input('custom_captcha_answer', ''));

        if (! is_array($challenge) || ! isset($challenge['answer'], $challenge['created_at'])
            || (int) $challenge['created_at'] < now()->subMinutes(self::CAPTCHA_TTL_MINUTES)->timestamp) {
            self::fail('The security code expired. We have loaded a new one, please try again.');
        }

        if ($answer === '') {
            self::fail('Please type the characters shown in the security image.');
        }

        if (! hash_equals((string) $challenge['answer'], self::hashCaptchaAnswer($answer))) {
            SecurityLog::record(SecurityLog::CAPTCHA_FAILED, $request, ['action' => $action, 'layer' => 'image']);

            self::fail('The security code did not match. Here is a new one.');
        }
    }

    private static function validateRecaptchaV2(Request $request, string $action): void
    {
        $secret = config('services.recaptcha.v2_secret_key');

        if (! $secret) {
            return;
        }

        $token = (string) $request->input('g-recaptcha-response');

        if ($token === '') {
            self::fail('Please tick the “I’m not a robot” box.');
        }

        $payload = self::verifyWithGoogle($secret, $token, $request);

        if (! ($payload['success'] ?? false)) {
            SecurityLog::record(SecurityLog::CAPTCHA_FAILED, $request, ['action' => $action, 'layer' => 'recaptcha_v2', 'errors' => $payload['error-codes'] ?? []]);

            self::fail('The reCAPTCHA check failed. Please try again.');
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
            self::stepUp($request, $action, 'The background security check did not load. Please refresh and try again.');
        }

        $payload = self::verifyWithGoogle($secret, $token, $request);
        $minimumScore = (float) config('services.recaptcha.v3_min_score', 0.5);
        $expectedHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (
            ! ($payload['success'] ?? false)
            || ($payload['action'] ?? null) !== $action
            || (float) ($payload['score'] ?? 0) < $minimumScore
            || ($expectedHost && isset($payload['hostname']) && ! in_array($payload['hostname'], [$expectedHost, $request->getHost()], true))
        ) {
            SecurityLog::record(SecurityLog::CAPTCHA_FAILED, $request, [
                'action' => $action,
                'layer' => 'recaptcha_v3',
                'score' => $payload['score'] ?? null,
                'reported_action' => $payload['action'] ?? null,
            ]);

            self::stepUp($request, $action, 'Our automated check was not sure you are human. Please try again.');
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
                ->retry(1, 250, throw: false)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ])
                ->json() ?? [];
        } catch (\Throwable) {
            self::fail('The security service is not responding. Please try again in a moment.');
        }
    }

    /**
     * Draws the code with per-glyph rotation, jitter, interference lines and
     * speckle so the answer cannot be scraped from markup.
     */
    private static function renderCaptchaImage(string $code): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $scale = 3;
        $glyphWidth = 11;
        $width = 16 + strlen($code) * $glyphWidth;
        $height = 26;
        $image = imagecreatetruecolor($width * $scale, $height * $scale);
        $background = imagecolorallocate($image, 21, 19, 16);
        imagefill($image, 0, 0, $background);

        for ($i = 0; $i < 60 * $scale; $i++) {
            $shade = random_int(40, 90);
            imagesetpixel($image, random_int(0, $width * $scale - 1), random_int(0, $height * $scale - 1), imagecolorallocate($image, $shade, $shade - 6, $shade - 14));
        }

        foreach (str_split($code) as $index => $character) {
            $glyph = imagecreatetruecolor(14, 18);
            $transparent = imagecolorallocatealpha($glyph, 0, 0, 0, 127);
            imagealphablending($glyph, false);
            imagefill($glyph, 0, 0, $transparent);
            imagesavealpha($glyph, true);
            imagestring($glyph, 5, 2, 1, $character, imagecolorallocate($glyph, random_int(215, 245), random_int(190, 215), random_int(130, 160)));

            $rotated = imagerotate($glyph, random_int(-22, 22), $transparent);
            imagealphablending($image, true);
            imagecopyresampled(
                $image,
                $rotated,
                (8 + $index * $glyphWidth + random_int(-1, 1)) * $scale,
                intdiv(random_int(2, 7) * $scale, 2),
                0,
                0,
                imagesx($rotated) * $scale,
                imagesy($rotated) * $scale,
                imagesx($rotated),
                imagesy($rotated)
            );
            imagedestroy($glyph);
            imagedestroy($rotated);
        }

        for ($line = 0; $line < 4; $line++) {
            imagesetthickness($image, intdiv(random_int(1, 2) * $scale, 2) + 1);
            imageline(
                $image,
                0,
                random_int(0, $height * $scale),
                $width * $scale,
                random_int(0, $height * $scale),
                imagecolorallocatealpha($image, random_int(160, 220), random_int(130, 170), random_int(80, 110), 70)
            );
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['captcha' => $message]);
    }

    private static function randomCaptchaCode(): string
    {
        $length = strlen(self::CUSTOM_CAPTCHA_ALPHABET);
        $code = '';

        for ($index = 0; $index < self::CAPTCHA_LENGTH; $index++) {
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
        return hash_hmac('sha256', self::normalizeCaptchaAnswer($answer), (string) config('app.key'));
    }

    private static function customCaptchaSessionKey(string $action): string
    {
        return self::CUSTOM_CAPTCHA_SESSION_PREFIX.$action;
    }
}
