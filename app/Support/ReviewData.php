<?php

namespace App\Support;

use App\Models\Movie;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Shapes reviews for the film page and its "load more" endpoint. */
class ReviewData
{
    public const PAGE_SIZE = 8;

    public const SORTS = ['recent', 'helpful', 'highest', 'lowest'];

    /**
     * @return array{items: list<array<string, mixed>>, next: ?int, total: int}
     */
    public static function page(Movie $movie, string $sort = 'recent', ?int $stars = null, bool $verifiedOnly = false, int $page = 1, ?int $viewerId = null): array
    {
        $query = Review::query()
            ->where('movie_id', $movie->id)
            ->where('is_approved', true)
            ->when($stars, fn (Builder $q) => $q->where('rating', $stars))
            ->when($verifiedOnly, fn (Builder $q) => $q->whereNotNull('booking_id'));

        $total = (clone $query)->count();

        match ($sort) {
            'helpful' => $query->orderByDesc('helpful_count')->orderByDesc('created_at'),
            'highest' => $query->orderByDesc('rating')->orderByDesc('helpful_count'),
            'lowest' => $query->orderBy('rating')->orderByDesc('helpful_count'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $reviews = $query
            ->with(['user:id,name,username,profile_picture,city,created_at', 'booking:id,show_id', 'booking.show:id,screen_id,show_date', 'booking.show.screen:id,theater_id,format,screen_name', 'booking.show.screen.theater:id,name'])
            ->forPage($page, self::PAGE_SIZE)
            ->get();

        $voted = $viewerId
            ? DB::table('review_votes')->where('user_id', $viewerId)->whereIn('review_id', $reviews->pluck('id'))->pluck('review_id')->flip()
            : collect();

        return [
            'items' => $reviews->map(fn (Review $review) => self::one($review, $voted->has($review->id)))->values()->all(),
            'next' => $page * self::PAGE_SIZE < $total ? $page + 1 : null,
            'total' => $total,
        ];
    }

    /** @return array<string, mixed> */
    public static function one(Review $review, bool $voted = false): array
    {
        $user = $review->user;
        $show = $review->booking?->show;

        return [
            'id' => $review->id,
            'author' => $user?->name ?? 'Former member',
            'username' => $user?->username,
            'avatar' => $user?->profile_picture ? (str_starts_with($user->profile_picture, 'http') ? $user->profile_picture : asset('storage/'.ltrim($user->profile_picture, '/'))) : null,
            'city' => $user?->city,
            'member_since' => $user?->created_at?->format('Y'),
            'rating' => (int) $review->rating,
            'title' => $review->title,
            'text' => $review->review_text,
            'spoilers' => (bool) $review->contains_spoilers,
            'verified' => $review->isVerified(),
            'watched' => $show ? trim(($show->screen?->theater?->name ?? '').' · '.(\App\Models\Screen::FORMAT_LABELS[$show->screen?->format] ?? 'Standard 2D'), ' ·') : null,
            'watched_on' => $show?->show_date?->format('j M Y'),
            'helpful' => (int) $review->helpful_count,
            'voted' => $voted,
            'ago' => $review->created_at?->diffForHumans(),
            'date' => $review->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{average: float, total: int, verified_pct: int, recommend_pct: int, distribution: list<array{stars: int, count: int}>}
     */
    public static function summary(Movie $movie): array
    {
        $row = Review::query()
            ->where('movie_id', $movie->id)
            ->where('is_approved', true)
            ->selectRaw('COUNT(*) AS total, AVG(rating) AS average, SUM(booking_id IS NOT NULL) AS verified, SUM(rating >= 4) AS recommend')
            ->first();
        $distribution = Review::query()
            ->where('movie_id', $movie->id)
            ->where('is_approved', true)
            ->selectRaw('rating, COUNT(*) AS total')
            ->groupBy('rating')
            ->pluck('total', 'rating');
        $total = (int) ($row->total ?? 0);

        return [
            'average' => round((float) ($row->average ?? 0), 1),
            'total' => $total,
            'verified_pct' => $total ? (int) round($row->verified / $total * 100) : 0,
            'recommend_pct' => $total ? (int) round($row->recommend / $total * 100) : 0,
            'distribution' => collect(range(5, 1))->map(fn (int $stars) => ['stars' => $stars, 'count' => (int) ($distribution[$stars] ?? 0)])->values()->all(),
        ];
    }
}
