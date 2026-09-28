<?php

namespace App\Support;

/**
 * Time-based one-time passwords (RFC 6238, SHA-1, 6 digits, 30 s), the
 * format every authenticator app (Google Authenticator, Microsoft
 * Authenticator, 1Password, Authy) understands. No network, no SMS.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    public static function secret(): string
    {
        $secret = '';
        for ($index = 0; $index < 32; $index++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    /** The otpauth:// link the QR code encodes. */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Accepts the current code and one step either side (clock drift). The
     * step that matched is returned so a code can never be replayed.
     */
    public static function verify(string $secret, string $code, ?int $lastUsedStep = null): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return null;
        }

        $now = intdiv(time(), self::PERIOD);
        foreach ([0, -1, 1] as $offset) {
            $step = $now + $offset;
            if ($lastUsedStep !== null && $step <= $lastUsedStep) {
                continue;
            }
            if (hash_equals(self::at($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    public static function at(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    /** @return list<string> Ten single-use codes like "7KQ2-M9XD". */
    public static function recoveryCodes(): array
    {
        return array_map(fn () => substr(self::secret(), 0, 4).'-'.substr(self::secret(), 0, 4), range(1, 10));
    }

    private static function decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $char) {
            $position = strpos(self::ALPHABET, $char);
            if ($position !== false) {
                $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
            }
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr((int) bindec($byte));
            }
        }

        return $bytes;
    }
}
