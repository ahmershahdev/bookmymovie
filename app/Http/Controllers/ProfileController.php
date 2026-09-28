<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use App\Support\ReviewData;
use Inertia\Response;

/** Public member pages: who someone is and what they thought of the films they saw. */
class ProfileController extends Controller
{
    public function show(string $username): Response
    {
        $user = User::query()->where('username', strtolower($username))->where('is_blocked', false)->firstOrFail();

        $reviews = Review::query()
            ->with(['photos', 'movie:id,title,slug,poster_image', 'booking:id,show_id', 'booking.show:id,screen_id,show_date', 'booking.show.screen:id,theater_id,format', 'booking.show.screen.theater:id,name'])
            ->where('user_id', $user->id)
            ->where('is_approved', true)
            ->latest()
            ->limit(30)
            ->get();
        $user->setRelation('reviews', $reviews);

        $average = $reviews->avg('rating');

        return $this->page('Public/Profile', [
            'member' => [
                'name' => $user->name,
                'username' => $user->username,
                'avatar' => $user->profile_picture ? asset('storage/'.$user->profile_picture) : null,
                'city' => $user->city,
                'bio' => $user->bio,
                'joined' => $user->created_at?->format('F Y'),
                'reviews' => $reviews->count(),
                'verified' => $reviews->filter->isVerified()->count(),
                'average' => $average ? round($average, 1) : null,
                'helpful' => (int) $reviews->sum('helpful_count'),
            ],
            'reviews' => $reviews->map(fn (Review $review) => [
                ...ReviewData::one($review->setRelation('user', $user)),
                'film' => $review->movie?->title,
                'film_slug' => $review->movie?->slug,
                'poster' => $review->movie?->publicMediaUrl($review->movie?->poster_image),
            ])->values(),
        ], [
            'title' => $user->name.' (@'.$user->username.') | BookMyMovie reviews',
            'description' => $reviews->count().' film reviews by '.$user->name.($user->city ? ' from '.$user->city : '').' on BookMyMovie.',
            'robots' => $reviews->count() >= 3 ? 'index, follow' : 'noindex, follow',
        ], [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Members', 'url' => null],
            ['label' => '@'.$user->username, 'url' => null],
        ]);
    }
}
