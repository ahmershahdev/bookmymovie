<?php

namespace App\Support;

use App\Models\Booking;
use RuntimeException;

/**
 * Apple Wallet (.pkpass) and Google Wallet ("Save to Google Wallet") tickets.
 * Both carry the same signed QR payload as the e-ticket. Each is only
 * offered once its signing credentials are configured in .env.
 */
class WalletPass
{
    public static function appleEnabled(): bool
    {
        $config = config('services.wallet.apple');

        return filled($config['pass_type_id'] ?? null) && filled($config['team_id'] ?? null)
            && is_file((string) ($config['certificate'] ?? '')) && is_file((string) ($config['wwdr'] ?? ''));
    }

    public static function googleEnabled(): bool
    {
        $config = config('services.wallet.google');

        return filled($config['issuer_id'] ?? null) && is_file((string) ($config['key_file'] ?? ''));
    }

    /* Apple ----------------------------------------------------------------- */

    /** Builds a signed .pkpass archive and returns its bytes. */
    public static function apple(Booking $booking): string
    {
        $config = config('services.wallet.apple');
        $startsAt = $booking->showStartsAt();
        $theater = $booking->show->screen->theater;

        $pass = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $config['pass_type_id'],
            'teamIdentifier' => $config['team_id'],
            'serialNumber' => $booking->booking_number,
            'organizationName' => 'BookMyMovie',
            'description' => 'Cinema ticket: '.$booking->show->movie->title,
            'logoText' => 'BookMyMovie',
            'backgroundColor' => 'rgb(10,10,10)',
            'foregroundColor' => 'rgb(242,240,234)',
            'labelColor' => 'rgb(227,255,59)',
            'relevantDate' => $startsAt?->toIso8601String(),
            'barcodes' => [[
                'format' => 'PKBarcodeFormatQR',
                'message' => BookingLifecycle::ticketPayload($booking->booking_number),
                'messageEncoding' => 'iso-8859-1',
                'altText' => $booking->booking_number,
            ]],
            'eventTicket' => [
                'primaryFields' => [['key' => 'film', 'label' => 'FILM', 'value' => $booking->show->movie->title]],
                'secondaryFields' => [
                    ['key' => 'date', 'label' => 'DATE', 'value' => $startsAt?->format('D j M')],
                    ['key' => 'time', 'label' => 'TIME', 'value' => $startsAt?->format('g:i A')],
                ],
                'auxiliaryFields' => [
                    ['key' => 'seats', 'label' => 'SEATS', 'value' => $booking->seats->map(fn ($seat) => $seat->seat->row_label.$seat->seat->seat_number)->join(' ')],
                    ['key' => 'screen', 'label' => 'SCREEN', 'value' => $booking->show->screen->screen_name],
                ],
                'backFields' => [
                    ['key' => 'cinema', 'label' => 'Cinema', 'value' => $theater->name.', '.$theater->address],
                    ['key' => 'booking', 'label' => 'Booking', 'value' => $booking->booking_number],
                    ['key' => 'payment', 'label' => 'Payment', 'value' => $booking->payment_status === 'paid' ? 'Paid' : 'Pay at the counter'],
                ],
            ],
        ];

        $files = [
            'pass.json' => json_encode($pass, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'icon.png' => (string) file_get_contents(public_path('images/favicon/apple-touch-icon.png')),
            'icon@2x.png' => (string) file_get_contents(public_path('images/favicon/apple-touch-icon.png')),
            'logo.png' => (string) file_get_contents(public_path('images/favicon/android-chrome-192x192.png')),
        ];

        $manifest = json_encode(array_map('sha1', $files), JSON_UNESCAPED_SLASHES);
        $files['manifest.json'] = $manifest;
        $files['signature'] = self::appleSignature($manifest, $config);

        return self::zip($files);
    }

    /** Detached PKCS#7 signature of manifest.json, in DER. */
    private static function appleSignature(string $manifest, array $config): string
    {
        if (! openssl_pkcs12_read((string) file_get_contents($config['certificate']), $credentials, (string) ($config['password'] ?? ''))) {
            throw new RuntimeException('Apple Wallet certificate could not be read.');
        }

        $directory = storage_path('app/private/wallet');
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $in = tempnam($directory, 'manifest');
        $out = tempnam($directory, 'signature');
        file_put_contents($in, $manifest);

        try {
            $signed = openssl_pkcs7_sign($in, $out, $credentials['cert'], $credentials['pkey'], [], PKCS7_BINARY | PKCS7_DETACHED, $config['wwdr']);
            if (! $signed) {
                throw new RuntimeException('Apple Wallet pass could not be signed.');
            }
            $smime = (string) file_get_contents($out);
            // The S/MIME body is base64 DER; Wallet wants raw DER.
            preg_match('/Content-Disposition:.*?\r?\n\r?\n(.*?)\r?\n\r?\n------/s', $smime, $match);

            return (string) base64_decode(preg_replace('/\s+/', '', $match[1] ?? ''));
        } finally {
            @unlink($in);
            @unlink($out);
        }
    }

    /**
     * Minimal store-only ZIP writer. The host PHP build has no zip
     * extension, and a .pkpass is just a ZIP of a handful of small files.
     *
     * @param  array<string, string>  $files
     */
    private static function zip(array $files): string
    {
        $data = '';
        $central = '';
        $offset = 0;

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $header = pack('VvvvvvVVVvv', 0x04034B50, 20, 0, 0, 0, 0x21, $crc, $size, $size, strlen($name), 0).$name;
            $data .= $header.$content;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0, 0, 0, 0x21, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 32, $offset).$name;
            $offset += strlen($header) + $size;
        }

        return $data.$central.pack('VvvvvVVv', 0x06054B50, 0, 0, count($files), count($files), strlen($central), $offset, 0);
    }

    /* Google ---------------------------------------------------------------- */

    /** A "Save to Google Wallet" link carrying a signed event ticket. */
    public static function googleSaveUrl(Booking $booking): string
    {
        $config = config('services.wallet.google');
        $key = json_decode((string) file_get_contents($config['key_file']), true) ?: [];
        $issuer = (string) $config['issuer_id'];
        $classId = $issuer.'.bookmymovie_'.$booking->show->movie->slug;
        $startsAt = $booking->showStartsAt();
        $theater = $booking->show->screen->theater;

        $claims = [
            'iss' => $key['client_email'] ?? '',
            'aud' => 'google',
            'typ' => 'savetowallet',
            'origins' => [rtrim((string) config('app.url'), '/')],
            'payload' => [
                'eventTicketClasses' => [[
                    'id' => $classId,
                    'issuerName' => 'BookMyMovie',
                    'reviewStatus' => 'UNDER_REVIEW',
                    'eventName' => ['defaultValue' => ['language' => 'en-US', 'value' => $booking->show->movie->title]],
                    'venue' => [
                        'name' => ['defaultValue' => ['language' => 'en-US', 'value' => $theater->name]],
                        'address' => ['defaultValue' => ['language' => 'en-US', 'value' => $theater->address]],
                    ],
                    'dateTime' => ['start' => $startsAt?->toIso8601String()],
                    'hexBackgroundColor' => '#0a0a0a',
                ]],
                'eventTicketObjects' => [[
                    'id' => $issuer.'.'.preg_replace('/[^A-Za-z0-9._-]/', '_', $booking->booking_number),
                    'classId' => $classId,
                    'state' => 'ACTIVE',
                    'ticketHolderName' => $booking->customer_name,
                    'ticketNumber' => $booking->booking_number,
                    'seatInfo' => ['seat' => ['defaultValue' => ['language' => 'en-US', 'value' => $booking->seats->map(fn ($seat) => $seat->seat->row_label.$seat->seat->seat_number)->join(' ')]]],
                    'barcode' => ['type' => 'QR_CODE', 'value' => BookingLifecycle::ticketPayload($booking->booking_number), 'alternateText' => $booking->booking_number],
                ]],
            ],
        ];

        $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode($claims);

        if (! openssl_sign($unsigned, $signature, (string) ($key['private_key'] ?? ''), OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Google Wallet ticket could not be signed.');
        }

        return 'https://pay.google.com/gp/v/save/'.$unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }
}
