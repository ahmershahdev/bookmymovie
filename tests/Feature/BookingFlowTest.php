<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function customer(): User
    {
        return User::query()->where('email', 'test@example.com')->firstOrFail();
    }

    private function freshCustomer(): User
    {
        return User::forceCreate([
            'name' => 'Rival Customer',
            'email' => 'rival.'.Str::random(6).'@example.com',
            'email_verified_at' => now(),
            'password' => 'Password@123',
        ]);
    }

    private function checkout(User $user, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->withCaptcha('checkout')->post('/checkout', [
            'idempotency_key' => (string) Str::uuid(),
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '0300 7654321',
            'terms' => '1',
            ...$this->captchaFields(),
            ...$overrides,
        ]);
    }

    public function test_customer_can_hold_seats_and_complete_checkout(): void
    {
        $user = $this->customer();
        [$show, $seats] = $this->bookableShow(2);
        $bookedBefore = $show->booked_seats;

        $this->actingAs($user)->post('/cart', ['show_id' => $show->id, 'seats' => $seats])
            ->assertRedirect(route('user.cart'));

        $this->assertSame(2, DB::table('v_seat_availability')->where('show_id', $show->id)->whereIn('seat_id', $seats)->where('seat_status', 'reserved')->count());

        $response = $this->checkout($user);
        $booking = Booking::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $response->assertRedirect(route('user.booking.show', $booking->booking_number));
        $this->assertSame(2, $booking->seat_count);
        $this->assertSame($bookedBefore + 2, $show->fresh()->booked_seats);
        $this->assertSame(2, BookingSeat::query()->where('booking_id', $booking->id)->where('seat_lock', 1)->count());
        $this->assertDatabaseHas('booking_events', ['booking_id' => $booking->id, 'event' => 'confirmed']);
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
    }

    public function test_resubmitting_the_same_checkout_creates_only_one_booking(): void
    {
        $user = $this->customer();
        [$show, $seats] = $this->bookableShow(1);
        $key = (string) Str::uuid();

        $this->actingAs($user)->post('/cart', ['show_id' => $show->id, 'seats' => $seats]);
        $first = $this->checkout($user, ['idempotency_key' => $key]);
        $second = $this->checkout($user, ['idempotency_key' => $key]);

        $this->assertSame(1, Booking::query()->where('idempotency_key', $key)->count());
        $this->assertSame($first->headers->get('Location'), $second->headers->get('Location'));
    }

    public function test_a_second_customer_cannot_hold_seats_already_in_someone_elses_cart(): void
    {
        [$show, $seats] = $this->bookableShow(2);

        $this->actingAs($this->customer())->post('/cart', ['show_id' => $show->id, 'seats' => $seats]);

        $this->actingAs($this->freshCustomer())
            ->from(route('movies.seats', ['slug' => $show->movie->slug, 'show' => $show->id]))
            ->post('/cart', ['show_id' => $show->id, 'seats' => [$seats[0]]])
            ->assertSessionHasErrors('seats');
    }

    public function test_the_database_refuses_a_second_live_booking_for_the_same_seat(): void
    {
        [$show, $seats] = $this->bookableShow(1);
        $user = $this->customer();
        $this->actingAs($user)->post('/cart', ['show_id' => $show->id, 'seats' => $seats]);
        $this->checkout($user);

        $existing = BookingSeat::query()->where('show_id', $show->id)->where('seat_id', $seats[0])->firstOrFail();

        $this->expectException(QueryException::class);

        // Bypass every application check: UNIQUE(show_id, seat_id, seat_lock) must still hold.
        DB::table('booking_seats')->insert([
            'booking_id' => $existing->booking_id,
            'seat_id' => $seats[0],
            'show_id' => $show->id,
            'seat_category_id' => $existing->seat_category_id,
            'ticket_type' => 'adult',
            'price_paid' => 100,
            'ticket_number' => 'DUPLICATE-'.Str::random(6),
            'seat_lock' => 1,
            'created_at' => now(),
        ]);
    }

    public function test_cancelling_releases_seats_and_returns_the_coupon_so_the_seat_can_be_resold(): void
    {
        $user = $this->customer();
        [$show, $seats] = $this->bookableShow(2);
        $coupon = Coupon::query()->where('code', 'WELCOME15')->firstOrFail();
        $usedBefore = $coupon->used_count;

        $this->actingAs($user)->post('/cart', ['show_id' => $show->id, 'seats' => $seats]);
        $this->checkout($user, ['coupon_code' => 'welcome15']);

        $booking = Booking::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertGreaterThan(0, (float) $booking->discount_amount);
        $this->assertSame($usedBefore + 1, $coupon->fresh()->used_count);

        $this->actingAs($user)->post(route('user.booking.cancel', $booking->booking_number), ['reason' => 'Plans changed'])
            ->assertRedirect(route('user.booking.show', $booking->booking_number));

        $this->assertSame('cancelled', $booking->fresh()->booking_status);
        $this->assertSame($usedBefore, $coupon->fresh()->used_count);
        $this->assertSame(0, BookingSeat::query()->where('booking_id', $booking->id)->whereNotNull('seat_lock')->count());

        // Before the seat_lock column this second sale was impossible.
        $rival = $this->freshCustomer();
        $this->actingAs($rival)->post('/cart', ['show_id' => $show->id, 'seats' => $seats])->assertRedirect(route('user.cart'));
        $this->checkout($rival);

        $this->assertSame(2, BookingSeat::query()->where('show_id', $show->id)->whereIn('seat_id', $seats)->where('seat_lock', 1)->count());
    }

    public function test_coupon_cannot_be_redeemed_beyond_its_usage_cap(): void
    {
        $coupon = Coupon::query()->where('code', 'MATINEE200')->firstOrFail();
        $coupon->forceFill(['max_uses' => 1, 'used_count' => 1, 'min_order_amount' => 0])->save();

        $user = $this->customer();
        [$show, $seats] = $this->bookableShow(1);
        $this->actingAs($user)->post('/cart', ['show_id' => $show->id, 'seats' => $seats]);

        $this->checkout($user, ['coupon_code' => 'MATINEE200'])->assertSessionHasErrors('coupon_code');
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_seat_limit_per_booking_is_enforced(): void
    {
        [$show, $seats] = $this->bookableShow(5);

        $this->actingAs($this->customer())
            ->from('/')
            ->post('/cart', ['show_id' => $show->id, 'seats' => $seats])
            ->assertSessionHasErrors('seats');
    }

    public function test_customers_cannot_see_or_cancel_other_peoples_bookings(): void
    {
        $booking = Booking::query()->where('user_id', '<>', $this->customer()->id)->firstOrFail();

        $this->actingAs($this->customer())->get(route('user.booking.show', $booking->booking_number))->assertNotFound();
        $this->actingAs($this->customer())->post(route('user.booking.cancel', $booking->booking_number))->assertNotFound();
    }
}
