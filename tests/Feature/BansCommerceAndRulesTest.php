<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BannedDevice;
use App\Models\Concession;
use App\Models\Coupon;
use App\Models\Movie;
use App\Models\User;
use App\Support\CleanText;
use App\Support\DeviceIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BansCommerceAndRulesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function asAdmin(): static
    {
        $admin = Admin::query()->where('is_demo', false)->firstOrFail();

        return $this->withSession(['admin_id' => $admin->id, 'admin_authenticated_at' => now()->timestamp]);
    }

    public function test_banning_a_member_blocks_every_browser_they_used(): void
    {
        $member = User::query()->where('is_blocked', false)->firstOrFail();
        $deviceId = str_repeat('a', 40);
        DB::table('user_devices')->insert([
            'user_id' => $member->id, 'device_hash' => hash('sha256', $deviceId), 'ip_address' => '198.51.100.9',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/140.0', 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);

        $this->asAdmin()->post(route('admin.users.ban', $member->id), ['reason' => 'Ban evasion test'])->assertRedirect();

        $this->assertTrue(BannedDevice::isBanned(hash('sha256', $deviceId)));
        // Same browser, brand-new network: still blocked.
        $this->withCookie(DeviceIdentity::COOKIE, $deviceId)
            ->withServerVariables(['REMOTE_ADDR' => '192.0.2.44'])
            ->get('/')->assertForbidden();

        $this->asAdmin()->post(route('admin.users.unban', $member->id))->assertRedirect();
        $this->assertFalse(BannedDevice::isBanned(hash('sha256', $deviceId)));
    }

    public function test_the_staff_area_stays_reachable_from_a_banned_network(): void
    {
        \App\Models\BannedIp::query()->create(['ip_address' => '203.0.113.5', 'reason' => 'Shared office NAT']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])->get('/')->assertForbidden();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])->get(route('admin.login'))->assertOk();
    }

    public function test_usernames_and_names_are_strict_and_clean(): void
    {
        foreach (['ab', '1abc', 'abc_', 'a__bc', 'ab12', 'admin', 'sh1t_lord', 'f_u_c_k_x', 'bhenchod'] as $bad) {
            $this->assertNotNull(CleanText::usernameProblem($bad), $bad.' should be rejected');
        }
        foreach (['ayesha_raza', 'omar2026', 'classic_films', 'nazia'] as $good) {
            $this->assertNull(CleanText::usernameProblem($good), $good.' should be allowed');
        }

        $this->post(route('user.register'), [
            'name' => 'Ayesha 99', 'username' => 'ayesha_ok', 'email' => 'ayesha.test@example.com', 'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!', 'terms' => true, 'custom_captcha_answer' => 'x',
        ])->assertSessionHasErrors();
    }

    public function test_a_coupon_limit_is_never_exceeded_by_concurrent_claims(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'LASTONE', 'discount_type' => 'percentage', 'discount_value' => 10, 'min_order_amount' => 0,
            'max_uses' => 1, 'used_count' => 0, 'max_uses_per_user' => 1, 'valid_from' => now()->subDay(), 'valid_until' => now()->addDay(), 'is_active' => true, 'created_by' => Admin::query()->value('id'),
        ]);

        // The same guarded UPDATE checkout uses: only one of many claims can win.
        $wins = 0;
        foreach (range(1, 50) as $attempt) {
            $wins += Coupon::query()->whereKey($coupon->id)
                ->where(fn ($query) => $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
                ->increment('used_count');
        }

        $this->assertSame(1, $wins);
        $this->assertSame(1, $coupon->refresh()->used_count);
    }

    public function test_admins_manage_coupons_stock_and_film_sales(): void
    {
        $this->asAdmin()->get(route('admin.commerce'))->assertOk();

        $this->asAdmin()->post(route('admin.coupons.store'), [
            'code' => 'rush50', 'type' => 'percentage', 'value' => 50, 'max_uses' => 100, 'per_user' => 1,
            'from' => now()->toDateTimeString(), 'until' => now()->addWeek()->toDateTimeString(),
        ])->assertSessionHasNoErrors();
        $coupon = Coupon::query()->where('code', 'RUSH50')->firstOrFail();

        $coupon->forceFill(['used_count' => 20])->save();
        $this->asAdmin()->put(route('admin.coupons.update', $coupon->id), [
            'type' => 'percentage', 'value' => 50, 'max_uses' => 10, 'per_user' => 1,
            'from' => now()->toDateTimeString(), 'until' => now()->addWeek()->toDateTimeString(),
        ])->assertSessionHasErrors('max_uses');

        $snack = Concession::query()->firstOrFail();
        $snack->forceFill(['stock' => 5])->save();
        $this->asAdmin()->put(route('admin.snacks.update', $snack->id), ['price' => 450, 'restock' => 20, 'active' => true])->assertSessionHasNoErrors();
        $this->assertSame(25, $snack->refresh()->stock);

        // Stock can only be taken while it lasts.
        $this->assertSame(1, Concession::query()->whereKey($snack->id)->where('stock', '>=', 25)->decrement('stock', 25));
        $this->assertSame(0, Concession::query()->whereKey($snack->id)->where('stock', '>=', 1)->decrement('stock', 1));

        $movie = Movie::query()->where('status', 'now_showing')->firstOrFail();
        $this->asAdmin()->put(route('admin.films.access', $movie->id), ['bookings' => false])->assertRedirect();
        $this->assertFalse((bool) $movie->refresh()->bookings_enabled);
    }
}
