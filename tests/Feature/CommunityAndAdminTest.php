<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BannedIp;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommunityAndAdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_every_film_has_45_to_55_reviews_and_seeded_ones_are_verified_by_a_past_booking(): void
    {
        $counts = Review::query()->selectRaw('movie_id, COUNT(*) AS total')->groupBy('movie_id')->pluck('total');
        $this->assertSame(Movie::query()->count(), $counts->count());
        $this->assertGreaterThanOrEqual(45, $counts->min());
        $this->assertLessThanOrEqual(55, $counts->max());

        $review = Review::query()->whereNotNull('booking_id')->with('booking.show')->firstOrFail();
        $this->assertSame($review->user_id, $review->booking->user_id);
        $this->assertSame($review->movie_id, $review->booking->show->movie_id);
        $this->assertTrue($review->booking->showStartsAt()->isPast());
    }

    public function test_a_member_cannot_review_before_their_show_has_started(): void
    {
        $booking = Booking::query()->with('show')->where('booking_status', 'confirmed')
            ->whereHas('show', fn ($query) => $query->where('show_date', '>', now()->toDateString()))->firstOrFail();
        $user = User::query()->findOrFail($booking->user_id);
        $movie = Movie::query()->findOrFail($booking->show->movie_id);
        Review::query()->where('user_id', $user->id)->where('movie_id', $movie->id)->delete();
        // Make sure no earlier, already-screened booking for the film exists for this member.
        Booking::query()->where('user_id', $user->id)->whereKeyNot($booking->id)->whereHas('show', fn ($query) => $query->where('movie_id', $movie->id))->delete();

        $this->actingAs($user)
            ->post(route('movies.reviews.store', $movie->slug), ['rating' => 5, 'review_text' => 'Fantastic, although I have not seen it yet at all.'])
            ->assertForbidden();
    }

    public function test_the_demo_admin_can_sign_in_but_cannot_change_anything_when_read_only(): void
    {
        config(['bookmymovie.admin.demo_read_only' => true]);
        $demo = Admin::query()->where('email', 'admin@bookmymovie.ahmershah.dev')->firstOrFail();
        $this->assertTrue($demo->is_demo);

        $session = ['admin_id' => $demo->id, 'admin_authenticated_at' => now()->timestamp];
        $this->withSession($session)->get(route('admin.users'))->assertOk();

        $member = User::query()->where('is_blocked', false)->firstOrFail();
        $this->withSession($session)->post(route('admin.users.ban', $member->id), ['reason' => 'Testing the demo lock'])
            ->assertRedirect()->assertSessionHasErrors('demo');
        $this->assertFalse($member->refresh()->is_blocked);
    }

    public function test_an_admin_can_ban_a_member_and_their_ip(): void
    {
        $admin = Admin::query()->where('is_demo', false)->firstOrFail();
        $member = User::query()->where('is_blocked', false)->firstOrFail();
        $member->forceFill(['last_login_ip' => '203.0.113.77'])->save();

        $this->withSession(['admin_id' => $admin->id, 'admin_authenticated_at' => now()->timestamp])
            ->post(route('admin.users.ban', $member->id), ['reason' => 'Card testing', 'ban_ip' => true])
            ->assertRedirect();

        $this->assertTrue($member->refresh()->is_blocked);
        $this->assertTrue(BannedIp::isBanned('203.0.113.77'));
        $this->get('/', ['REMOTE_ADDR' => '203.0.113.77'])->assertForbidden();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77'])->get('/')->assertForbidden();
    }

    public function test_usernames_are_unique_and_every_account_gets_one(): void
    {
        $this->assertSame(0, User::query()->whereNull('username')->count());
        $this->assertSame(User::query()->count(), User::query()->distinct()->count('username'));

        $taken = User::query()->value('username');
        $this->getJson(route('username.check', ['u' => $taken]))->assertJson(['valid' => true, 'available' => false]);
        $this->getJson(route('username.check', ['u' => 'admin']))->assertJson(['valid' => false]);
        $this->getJson(route('username.check', ['u' => 'brand_new_name_42']))->assertJson(['available' => true]);
    }

    public function test_helpful_votes_toggle_and_keep_the_count_in_step(): void
    {
        $review = Review::query()->firstOrFail();
        $voter = User::query()->whereKeyNot($review->user_id)->firstOrFail();
        $before = $review->helpful_count;

        $this->actingAs($voter)->postJson(route('reviews.helpful', $review->id))->assertJson(['voted' => true, 'helpful' => $before + 1]);
        $this->actingAs($voter)->postJson(route('reviews.helpful', $review->id))->assertJson(['voted' => false, 'helpful' => $before]);
        $this->assertSame(0, DB::table('review_votes')->where('review_id', $review->id)->where('user_id', $voter->id)->count());
    }
}
