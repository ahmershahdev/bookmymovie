<?php

namespace Tests\Feature;

use App\Jobs\ProcessShowWaitlist;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingSplit;
use App\Models\Cart;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Show;
use App\Models\User;
use App\Support\SplitPayments;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GroupWaitlistRolesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function upcomingShowWithFreeSeats(int $needed): array
    {
        foreach (Show::query()->where('status', 'scheduled')->where('show_date', '>', now()->toDateString())->with('movie')->get() as $show) {
            if (! $show->movie?->bookings_enabled) {
                continue;
            }
            $seats = DB::table('v_seat_availability')->where('show_id', $show->id)->where('seat_status', 'available')
                ->whereNotNull('kids_price')->limit($needed)->pluck('seat_id')->all();
            if (count($seats) === $needed) {
                return [$show, $seats];
            }
        }
        $this->fail('No upcoming show with enough child-priced seats.');
    }

    public function test_one_booking_can_mix_adult_and_child_tickets(): void
    {
        [$show, $seats] = $this->upcomingShowWithFreeSeats(2);
        $user = User::query()->where('is_blocked', false)->firstOrFail();
        Cart::query()->where('user_id', $user->id)->delete();

        $this->actingAs($user)->post(route('user.cart'), [
            'show_id' => $show->id, 'seats' => $seats, 'ticket_types' => [$seats[0] => 'adult', $seats[1] => 'kid'],
        ])->assertRedirect(route('user.cart'));

        $types = DB::table('cart_items')->whereIn('seat_id', $seats)->where('show_id', $show->id)->pluck('ticket_type', 'seat_id');
        $this->assertSame('adult', $types[$seats[0]]);
        $this->assertSame('kid', $types[$seats[1]]);

        // Switch the child back to an adult from the cart.
        $item = DB::table('cart_items')->where('show_id', $show->id)->where('seat_id', $seats[1])->first();
        $this->actingAs($user)->patch(route('user.cart.item', $item->id), ['ticket_type' => 'adult'])->assertRedirect();
        $this->assertSame('adult', DB::table('cart_items')->where('id', $item->id)->value('ticket_type'));
    }

    public function test_waitlist_is_notified_first_come_when_seats_free_up(): void
    {
        [$show] = $this->upcomingShowWithFreeSeats(1);
        [$first, $second] = User::query()->where('is_blocked', false)->limit(2)->get()->all();

        $this->actingAs($first)->post(route('waitlist.join', $show->id), ['seats' => 1])->assertRedirect();
        $this->actingAs($second)->post(route('waitlist.join', $show->id), ['seats' => 1])->assertRedirect();
        DB::table('show_waitlists')->where('user_id', $second->id)->update(['created_at' => now()->addSecond()]);

        // Make exactly one seat free by holding every other one.
        $free = DB::table('v_seat_availability')->where('show_id', $show->id)->where('seat_status', 'available')->pluck('seat_id');
        $holder = User::query()->where('is_blocked', false)->whereNotIn('id', [$first->id, $second->id])->firstOrFail();
        $cart = Cart::query()->create(['user_id' => $holder->id, 'expires_at' => now()->addMinutes(10)]);
        DB::table('cart_items')->insert($free->slice(1)->map(fn ($seat) => [
            'cart_id' => $cart->id, 'show_id' => $show->id, 'seat_id' => $seat,
            'seat_category_id' => DB::table('seats')->where('id', $seat)->value('seat_category_id'), 'ticket_type' => 'adult', 'price' => 100, 'added_at' => now(),
        ])->values()->all());

        (new ProcessShowWaitlist($show->id))->handle();

        $this->assertNotNull(DB::table('show_waitlists')->where('user_id', $first->id)->value('notified_at'));
        $this->assertNull(DB::table('show_waitlists')->where('user_id', $second->id)->value('notified_at'));
        $this->assertTrue(DB::table('notifications')->where('user_id', $first->id)->where('type', 'waitlist')->exists());
    }

    public function test_split_shares_settle_the_booking_only_when_all_are_paid(): void
    {
        $booking = Booking::query()->where('booking_status', 'confirmed')->where('payment_status', '<>', 'paid')->where('total_amount', '>', 0)
            ->whereHas('show', fn ($query) => $query->where('show_date', '>', now()->addDay()->toDateString()))->firstOrFail();

        $this->actingAs($booking->user)->post(route('user.booking.split', $booking->booking_number), ['people' => 2])->assertSessionHasNoErrors();
        $shares = BookingSplit::query()->where('booking_id', $booking->id)->get();
        $this->assertCount(2, $shares);
        $this->assertEqualsWithDelta((float) $booking->total_amount, (float) $shares->sum('amount'), 0.001);

        // A friend opens their link without an account, and sees no contact details.
        $friend = $shares->firstWhere('is_host', false);
        $this->app['auth']->forgetGuards();
        \Illuminate\Support\Facades\Auth::logout();
        $this->get(route('split.show', $friend->getRawOriginal('token')))->assertOk()->assertDontSee((string) $booking->customer_email);

        SplitPayments::markPaid($friend, 'card', 'pi_test');
        $this->assertNotSame('paid', $booking->refresh()->payment_status);

        SplitPayments::markPaid($shares->firstWhere('is_host', true), 'card', 'pi_test2');
        $this->assertSame('paid', $booking->refresh()->payment_status);

        // Paying twice is a no-op; changing the split after payment is refused.
        SplitPayments::markPaid($friend, 'card', 'pi_again');
        $this->actingAs($booking->user)->post(route('user.booking.split', $booking->booking_number), ['people' => 2])->assertSessionHasErrors('split');
    }

    public function test_box_office_staff_cannot_manage_but_can_view(): void
    {
        $staff = Admin::query()->create(['name' => 'Door Staff', 'email' => 'door@bookmymovie.test', 'password' => 'Door#Pass2026', 'role' => 'box_office', 'is_active' => true]);
        $session = ['admin_id' => $staff->id, 'admin_authenticated_at' => now()->timestamp];

        $this->withSession($session)->get(route('admin.dashboard'))->assertOk();
        $this->withSession($session)->get(route('admin.analytics'))->assertOk();
        $this->withSession($session)->get(route('admin.commerce'))->assertForbidden();
        $this->withSession($session)->get(route('admin.staff'))->assertForbidden();
        $member = User::query()->where('is_blocked', false)->firstOrFail();
        $this->withSession($session)->post(route('admin.users.ban', $member->id), ['reason' => 'Should not work'])->assertSessionHasErrors('role');
        $this->assertFalse($member->refresh()->is_blocked);
    }

    public function test_admin_two_step_sign_in_requires_the_authenticator_code(): void
    {
        $secret = Totp::secret();
        $admin = Admin::query()->create(['name' => 'Owner Two', 'email' => 'owner2@bookmymovie.test', 'password' => 'Owner#Pass2026', 'role' => 'superadmin', 'is_active' => true]);
        $admin->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => []])->save();

        $this->withSession(['admin_2fa_pending' => ['id' => $admin->id, 'until' => now()->addMinutes(5)->timestamp]])
            ->post(route('admin.two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');

        $this->withSession(['admin_2fa_pending' => ['id' => $admin->id, 'until' => now()->addMinutes(5)->timestamp]])
            ->post(route('admin.two-factor.verify'), ['code' => Totp::at($secret, intdiv(time(), 30))])
            ->assertRedirect(route('admin.dashboard'))->assertSessionHas('admin_id', $admin->id);
    }

    public function test_review_photos_are_re_encoded_and_capped_at_three(): void
    {
        Storage::fake('public');
        $review = Review::query()->whereNotNull('booking_id')->with('booking.show', 'movie')->firstOrFail();
        $user = User::query()->findOrFail($review->user_id);

        $this->actingAs($user)->post(route('movies.reviews.store', $review->movie->slug), [
            'rating' => 4, 'review_text' => 'Great sound, a lovely print and a very strong final act.',
            'photos' => [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.png', 400, 400)],
        ])->assertSessionHasNoErrors();

        $photos = $review->refresh()->photos;
        $this->assertCount(2, $photos);
        foreach ($photos as $photo) {
            $this->assertStringEndsWith('.webp', $photo->path);
            Storage::disk('public')->assertExists([$photo->path, str_replace('.webp', '-sm.webp', $photo->path)]);
        }

        $this->actingAs($user)->post(route('movies.reviews.store', $review->movie->slug), [
            'rating' => 4, 'review_text' => 'Great sound, a lovely print and a very strong final act.',
            'photos' => array_map(fn ($name) => UploadedFile::fake()->image($name.'.jpg', 400, 400), ['c', 'd', 'e', 'f']),
        ])->assertSessionHasErrors('photos');
    }

    public function test_push_subscriptions_only_accept_real_push_services(): void
    {
        $user = User::query()->where('is_blocked', false)->firstOrFail();
        $keys = ['p256dh' => str_repeat('A', 87), 'auth' => str_repeat('B', 22)];

        $this->actingAs($user)->postJson(route('push.subscribe'), ['endpoint' => 'https://evil.example.com/hook', 'keys' => $keys])->assertStatus(422);
        $this->actingAs($user)->postJson(route('push.subscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123', 'keys' => $keys])->assertOk();
        $this->assertSame(1, DB::table('push_subscriptions')->where('user_id', $user->id)->count());
    }

    public function test_opening_a_film_for_booking_announces_it_once(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $movie = Movie::query()->where('status', 'coming_soon')->firstOrFail();

        $movie->forceFill(['status' => 'now_showing', 'bookings_enabled' => true])->save();
        $movie->forceFill(['tagline' => 'Edited later'])->save();

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\AnnounceBookingsOpen::class, 1);
    }
}
