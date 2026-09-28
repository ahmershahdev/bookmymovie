<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Payments\PaymentGateway;
use App\Support\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentsSecurityAndUrlsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_old_query_string_links_redirect_to_clean_urls(): void
    {
        $this->get('/movies?status=now_showing')->assertRedirect('/movies/now-showing')->assertStatus(301);
        $this->get('/search?q=kestrel')->assertRedirect('/search/kestrel')->assertStatus(301);
        $this->get('/movies/now-showing')->assertOk();
        $this->get('/movies/coming-soon')->assertOk();
        $this->get('/search/kestrel')->assertOk();
    }

    public function test_only_cash_is_offered_until_gateway_keys_exist(): void
    {
        config(['payments.jazzcash.merchant_id' => null, 'payments.easypaisa.store_id' => null, 'payments.stripe.secret' => null]);
        $this->assertSame(['cod'], array_column(PaymentGateway::available(), 'key'));

        config(['payments.jazzcash' => ['merchant_id' => 'MC1', 'password' => 'p', 'integrity_salt' => 's', 'endpoint' => 'https://example.test']]);
        $this->assertContains('jazzcash', array_column(PaymentGateway::available(), 'key'));
    }

    public function test_jazzcash_callback_needs_a_valid_signature_and_matching_amount(): void
    {
        config(['payments.jazzcash' => ['merchant_id' => 'MC1', 'password' => 'secret', 'integrity_salt' => 'salt123', 'endpoint' => 'https://example.test']]);

        $booking = Booking::query()->with('payment')->whereHas('payment', fn ($query) => $query->where('status', 'pending'))->firstOrFail();
        $booking->forceFill(['payment_method' => 'jazzcash', 'payment_status' => 'pending'])->save();
        $booking->payment->forceFill(['payment_method' => 'jazzcash', 'gateway_reference' => 'T20260928000001'])->save();

        $response = [
            'pp_TxnRefNo' => 'T20260928000001',
            'pp_Amount' => (string) (int) round((float) $booking->payment->amount * 100),
            'pp_ResponseCode' => '000',
            'pp_ResponseMessage' => 'Thank you',
            'pp_RetreivalReferenceNo' => '123456',
        ];

        // A forged callback (no or wrong signature) changes nothing.
        $this->assertNull(PaymentGateway::handleJazzcash([...$response, 'pp_SecureHash' => 'FORGED']));
        $this->assertSame('pending', $booking->payment->refresh()->status);

        // A correctly signed success marks it paid.
        $signed = [...$response, 'pp_SecureHash' => PaymentGateway::jazzcashHash($response)];
        $this->post('/payments/jazzcash/callback', $signed)->assertRedirect(route('payments.return', $booking->booking_number));

        $this->assertSame('paid', $booking->payment->refresh()->status);
        $this->assertSame('paid', $booking->refresh()->payment_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.paid', 'subject_id' => $booking->id]);
    }

    public function test_passwords_are_hashed_with_argon2id(): void
    {
        $this->assertSame('argon2id', password_get_info(Hash::make('Secret123'))['algoName']);
        // Older bcrypt hashes still verify, so existing accounts keep working.
        $this->assertTrue(Hash::check('Secret123', password_hash('Secret123', PASSWORD_BCRYPT)));
    }

    public function test_admin_payment_audit_redacts_secrets(): void
    {
        AuditLog::record('test.action', null, ['password' => 'hunter2', 'name' => 'Visible'], null, 'system');

        $changes = json_decode((string) DB::table('audit_logs')->where('action', 'test.action')->value('changes'), true);
        $this->assertSame('[redacted]', $changes['password']);
        $this->assertSame('Visible', $changes['name']);
    }

    public function test_payment_model_relation(): void
    {
        $payment = Payment::query()->firstOrFail();
        $this->assertInstanceOf(Booking::class, $payment->booking);
    }
}
