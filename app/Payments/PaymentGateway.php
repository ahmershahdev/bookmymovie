<?php

namespace App\Payments;

use App\Models\Booking;
use App\Models\BookingEvent;
use App\Models\Payment;
use App\Support\AuditLog;
use App\Support\TransactionalMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\StripeClient;

/**
 * One place for every way to pay. Cash at the counter is always offered;
 * each online method only appears once its merchant keys are configured.
 *
 * Trust rules: a payment is only marked paid after the provider's answer is
 * verified server-side (JazzCash HMAC signature, Stripe API lookup,
 * Easypaisa IPN fetched from Easypaisa itself). Browser redirects alone
 * never mark a booking as paid.
 */
class PaymentGateway
{
    public const METHODS = [
        'cod' => ['label' => 'Pay at the counter', 'detail' => 'Cash or card at the box office, 20 minutes before the show.'],
        'jazzcash' => ['label' => 'JazzCash', 'detail' => 'Mobile wallet, voucher or card through JazzCash.'],
        'easypaisa' => ['label' => 'Easypaisa', 'detail' => 'Easypaisa mobile account or shop payment.'],
        'card' => ['label' => 'Credit or debit card', 'detail' => 'Visa, Mastercard and UnionPay, secured by Stripe.'],
    ];

    /**
     * @return list<array{key: string, label: string, detail: string}>
     */
    public static function available(): array
    {
        return collect(self::METHODS)
            ->filter(fn ($method, string $key) => self::enabled($key))
            ->map(fn ($method, string $key) => ['key' => $key, ...$method])
            ->values()
            ->all();
    }

    public static function enabled(string $method): bool
    {
        return match ($method) {
            'cod' => true,
            'jazzcash' => filled(config('payments.jazzcash.merchant_id')) && filled(config('payments.jazzcash.password')) && filled(config('payments.jazzcash.integrity_salt')),
            'easypaisa' => filled(config('payments.easypaisa.store_id')) && filled(config('payments.easypaisa.hash_key')),
            'card' => filled(config('payments.stripe.secret')),
            default => false,
        };
    }

    /**
     * What the browser should do next for an online payment: either POST a
     * signed form to the provider or follow a redirect URL.
     *
     * @return array{type: 'form', action: string, fields: array<string, string>}|array{type: 'redirect', url: string}
     */
    public static function start(Booking $booking): array
    {
        $payment = $booking->payment ?? throw new RuntimeException('Booking has no payment record.');

        $result = match ($booking->payment_method) {
            'jazzcash' => self::jazzcashForm($booking),
            'easypaisa' => self::easypaisaForm($booking),
            'card' => self::stripeSession($booking),
            default => throw new RuntimeException('This booking is paid at the counter.'),
        };

        $payment->forceFill(['gateway_started_at' => now()])->save();

        return $result;
    }

    /* JazzCash --------------------------------------------------------------- */

    private static function jazzcashForm(Booking $booking): array
    {
        $now = now('Asia/Karachi');
        $reference = 'T'.$now->format('YmdHis').str_pad((string) ($booking->id % 1000), 3, '0', STR_PAD_LEFT);

        $fields = [
            'pp_Version' => '1.1',
            'pp_TxnType' => '',
            'pp_Language' => 'EN',
            'pp_MerchantID' => (string) config('payments.jazzcash.merchant_id'),
            'pp_SubMerchantID' => '',
            'pp_Password' => (string) config('payments.jazzcash.password'),
            'pp_BankID' => '',
            'pp_ProductID' => '',
            'pp_TxnRefNo' => $reference,
            'pp_Amount' => (string) (int) round((float) $booking->total_amount * 100),
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $now->format('YmdHis'),
            'pp_BillReference' => $booking->booking_number,
            'pp_Description' => 'BookMyMovie booking '.$booking->booking_number,
            'pp_TxnExpiryDateTime' => $now->copy()->addMinutes(30)->format('YmdHis'),
            'pp_ReturnURL' => route('payments.jazzcash.callback'),
            'ppmpf_1' => $booking->booking_number,
            'ppmpf_2' => '',
            'ppmpf_3' => '',
            'ppmpf_4' => '',
            'ppmpf_5' => '',
        ];
        $fields['pp_SecureHash'] = self::jazzcashHash($fields);

        $booking->payment->forceFill(['gateway_reference' => $reference])->save();

        return ['type' => 'form', 'action' => (string) config('payments.jazzcash.endpoint'), 'fields' => $fields];
    }

    /**
     * HMAC-SHA256 over the integrity salt and every non-empty pp_ field,
     * sorted by name and joined with "&".
     *
     * @param  array<string, mixed>  $fields
     */
    public static function jazzcashHash(array $fields): string
    {
        $salt = (string) config('payments.jazzcash.integrity_salt');
        $values = collect($fields)
            ->filter(fn ($value, $key) => str_starts_with(strtolower((string) $key), 'pp') && strtolower((string) $key) !== 'pp_securehash' && (string) $value !== '')
            ->sortKeys()
            ->values()
            ->all();

        return strtoupper(hash_hmac('sha256', $salt.'&'.implode('&', $values), $salt));
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function handleJazzcash(array $response): ?Booking
    {
        $reference = (string) ($response['pp_TxnRefNo'] ?? '');
        $payment = Payment::query()->where('gateway_reference', $reference)->first();

        if (! $payment || ! hash_equals(self::jazzcashHash($response), strtoupper((string) ($response['pp_SecureHash'] ?? '')))) {
            Log::warning('JazzCash callback rejected', ['reference' => $reference]);

            return null;
        }

        $expected = (string) (int) round((float) $payment->amount * 100);
        $success = ($response['pp_ResponseCode'] ?? '') === '000' && (string) ($response['pp_Amount'] ?? '') === $expected;

        return self::settle($payment, $success, (string) ($response['pp_RetreivalReferenceNo'] ?? $reference), $response);
    }

    /* Easypaisa ------------------------------------------------------------------ */

    private static function easypaisaForm(Booking $booking): array
    {
        $fields = [
            'amount' => number_format((float) $booking->total_amount, 1, '.', ''),
            'autoRedirect' => '1',
            'emailAddr' => (string) $booking->customer_email,
            'expiryDate' => now('Asia/Karachi')->addMinutes(30)->format('Ymd His'),
            'mobileNum' => preg_replace('/\D+/', '', (string) $booking->customer_phone),
            'orderRefNum' => $booking->booking_number,
            'paymentMethod' => 'MA_PAYMENT_METHOD',
            'postBackURL' => route('payments.easypaisa.confirm', $booking->booking_number),
            'storeId' => (string) config('payments.easypaisa.store_id'),
        ];
        ksort($fields);
        $fields['merchantHashedReq'] = self::easypaisaHash($fields);

        $booking->payment->forceFill(['gateway_reference' => $booking->booking_number])->save();

        return ['type' => 'form', 'action' => (string) config('payments.easypaisa.endpoint'), 'fields' => $fields];
    }

    /**
     * AES-128-ECB of the sorted query string with the store's hash key.
     *
     * @param  array<string, string>  $fields
     */
    public static function easypaisaHash(array $fields): string
    {
        $query = collect($fields)->map(fn ($value, $key) => $key.'='.$value)->implode('&');

        return base64_encode((string) openssl_encrypt($query, 'AES-128-ECB', (string) config('payments.easypaisa.hash_key'), OPENSSL_RAW_DATA));
    }

    /**
     * Second leg of the hosted checkout: hand the auth token back to
     * Easypaisa so the customer can approve the payment.
     *
     * @return array{type: 'form', action: string, fields: array<string, string>}
     */
    public static function easypaisaConfirmForm(Booking $booking, string $authToken): array
    {
        return [
            'type' => 'form',
            'action' => (string) config('payments.easypaisa.confirm_endpoint'),
            'fields' => ['auth_token' => $authToken, 'postBackURL' => route('payments.return', $booking->booking_number)],
        ];
    }

    /**
     * Instant payment notification: Easypaisa calls us with a URL on its own
     * domain that describes the transaction; only that answer is trusted.
     */
    public static function handleEasypaisaIpn(string $url): ?Booking
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! str_ends_with($host, 'easypaisa.com.pk')) {
            Log::warning('Easypaisa IPN rejected: untrusted host', ['host' => $host]);

            return null;
        }

        $data = Http::timeout(10)->get($url)->json() ?? [];
        $payment = Payment::query()->where('gateway_reference', (string) ($data['order_id'] ?? ''))->first();

        if (! $payment) {
            return null;
        }

        $paid = strtoupper((string) ($data['transaction_status'] ?? '')) === 'PAID'
            && abs((float) ($data['transaction_amount'] ?? 0) - (float) $payment->amount) < 0.01;

        return self::settle($payment, $paid, (string) ($data['transaction_id'] ?? $payment->gateway_reference), $data);
    }

    /* Cards (Stripe Checkout) ---------------------------------------------------- */

    private static function stripeSession(Booking $booking): array
    {
        $stripe = new StripeClient((string) config('payments.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $booking->customer_email,
            'client_reference_id' => $booking->booking_number,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'pkr',
                    'unit_amount' => (int) round((float) $booking->total_amount * 100),
                    'product_data' => ['name' => 'BookMyMovie booking '.$booking->booking_number],
                ],
            ]],
            'success_url' => route('payments.return', $booking->booking_number),
            'cancel_url' => route('payments.return', $booking->booking_number),
            'expires_at' => now()->addMinutes(30)->timestamp,
        ], ['idempotency_key' => 'bmm-'.$booking->booking_number.'-'.$booking->payment->id.'-'.now()->format('YmdHi')]);

        $booking->payment->forceFill(['gateway_reference' => $session->id])->save();

        return ['type' => 'redirect', 'url' => (string) $session->url];
    }

    /**
     * Customers land back here from Stripe; the session is looked up with
     * the secret key, so a crafted return URL cannot fake a payment.
     */
    public static function refreshStripe(Payment $payment): ?Booking
    {
        if (! self::enabled('card') || ! $payment->gateway_reference || $payment->status === 'paid') {
            return $payment->booking;
        }

        $session = (new StripeClient((string) config('payments.stripe.secret')))->checkout->sessions->retrieve($payment->gateway_reference);

        if ($session->payment_status === 'paid') {
            return self::settle($payment, true, (string) $session->payment_intent, ['session' => $session->id]);
        }

        return $payment->booking;
    }

    /* Settlement ----------------------------------------------------------------- */

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function settle(Payment $payment, bool $success, string $transaction, array $payload): Booking
    {
        $booking = DB::transaction(function () use ($payment, $success, $transaction, $payload) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $booking = Booking::query()->lockForUpdate()->findOrFail($payment->booking_id);

            if ($payment->status === 'paid') {
                return $booking;
            }

            unset($payload['pp_Password'], $payload['pp_SecureHash']);

            if ($success) {
                $payment->forceFill(['status' => 'paid', 'paid_at' => now(), 'transaction_reference' => mb_substr($transaction, 0, 100), 'gateway_payload' => $payload])->save();
                $booking->forceFill(['payment_status' => 'paid'])->save();
                BookingEvent::create(['booking_id' => $booking->id, 'event' => 'paid', 'note' => 'Paid online with '.self::METHODS[$booking->payment_method]['label'].'.', 'actor_type' => 'system']);
            } else {
                // A failed online attempt falls back to paying at the counter.
                $payment->forceFill(['gateway_payload' => $payload, 'notes' => 'Online payment was not completed. Pay at the box office instead.'])->save();
                BookingEvent::create(['booking_id' => $booking->id, 'event' => 'payment_failed', 'note' => 'Online payment was not completed.', 'actor_type' => 'system']);
            }

            return $booking;
        });

        AuditLog::record($success ? 'payment.paid' : 'payment.failed', $booking, ['method' => $booking->payment_method, 'amount' => (float) $payment->amount], null, 'system');

        if ($success) {
            $booking->loadMissing('user');
            TransactionalMailer::paymentReceived($booking->user, $booking);
        }

        return $booking->refresh();
    }
}
