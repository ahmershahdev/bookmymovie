<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Screen;
use App\Models\Wishlist;
use App\Support\CleanText;
use App\Support\ReviewData;
use App\Support\ReviewPhotos;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Response;

class MovieController extends Controller
{
    private const SORTS = [
        'popular' => 'Most popular',
        'rating' => 'Highest rated',
        'newest' => 'Newest releases',
        'title' => 'Title A–Z',
        'runtime' => 'Shortest first',
    ];

    private const STATUSES = [
        'now-showing' => ['now_showing', 'Now showing'],
        'coming-soon' => ['coming_soon', 'Coming soon'],
        'ended' => ['ended', 'Recently ended'],
    ];

    public function index(Request $request): Response|RedirectResponse
    {
        // Old filter links (?status=now_showing) move to their clean address.
        if ($status = array_search($request->query('status'), array_map(fn ($item) => $item[0], self::STATUSES), true)) {
            return redirect()->route('movies.status', $status, 301);
        }

        return $this->catalogue();
    }

    public function status(string $status): Response
    {
        return $this->catalogue(null, $status);
    }

    public function genre(string $slug): Response
    {
        $genre = Genre::query()->where('slug', $slug)->firstOrFail();

        return $this->catalogue($genre);
    }

    /**
     * Every film in the chosen slice goes to the browser at once (the slate
     * is small); search, language, rating and sort then filter instantly
     * on screen with no reloads and no query strings.
     */
    private function catalogue(?Genre $fixedGenre = null, ?string $status = null): Response
    {
        $statusKey = $status ? self::STATUSES[$status][0] : null;

        $movies = Movie::query()
            ->withCardMetrics()
            ->with(['genres', 'credits.person'])
            ->when($fixedGenre, fn (Builder $query) => $query->whereHas('genres', fn (Builder $genres) => $genres->whereKey($fixedGenre->id)))
            ->when($statusKey, fn (Builder $query) => $query->where('status', $statusKey), fn (Builder $query) => $query->publiclyListed())
            ->orderByRaw("FIELD(status, 'now_showing', 'coming_soon', 'ended')")
            ->orderByDesc('total_reviews')
            ->orderByDesc('average_rating')
            ->get();

        $genres = Genre::query()
            ->withCount(['movies' => fn (Builder $query) => $query->publiclyListed()])
            ->orderBy('name')
            ->get()
            ->filter(fn (Genre $genre) => $genre->movies_count > 0)
            ->values();

        $breadcrumbs = match (true) {
            (bool) $fixedGenre => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Films', 'url' => route('movies.index')],
                ['label' => $fixedGenre->name, 'url' => null],
            ],
            (bool) $status => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Films', 'url' => route('movies.index')],
                ['label' => self::STATUSES[$status][1], 'url' => null],
            ],
            default => null,
        };

        $label = $fixedGenre?->name ?? ($status ? self::STATUSES[$status][1] : null);

        return $this->page('Movies/Index', [
            'movies' => $movies->map(fn (Movie $movie) => [
                ...$movie->toCardArray(),
                'genre_slugs' => $movie->genres->pluck('slug')->all(),
                'released_at' => $movie->release_date?->toDateString(),
                // Searchable text: people as well as titles.
                'people' => $movie->credits->map(fn ($credit) => $credit->person?->name)->filter()->unique()->implode(' '),
            ])->values(),
            'genres' => $genres->map(fn (Genre $genre) => ['name' => $genre->name, 'slug' => $genre->slug, 'count' => $genre->movies_count])->values(),
            'fixedGenre' => $fixedGenre ? ['name' => $fixedGenre->name, 'slug' => $fixedGenre->slug] : null,
            'status' => $status,
            'statuses' => collect(self::STATUSES)->map(fn ($item, $slug) => ['slug' => $slug, 'label' => $item[1]])->values(),
            'languages' => Movie::query()->publiclyListed()->distinct()->orderBy('language')->pluck('language'),
            'certificates' => Movie::query()->publiclyListed()->whereNotNull('certificate_rating')->distinct()->orderBy('certificate_rating')->pluck('certificate_rating'),
            'sorts' => self::SORTS,
            'statusCounts' => Movie::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
        ], [
            'title' => $label ? $label.' Films in Cinemas | BookMyMovie' : 'Films in Cinemas: Showtimes & Tickets | BookMyMovie',
            'description' => $fixedGenre
                ? 'Every '.strtolower($fixedGenre->name).' film playing now or coming soon to BookMyMovie cinemas, with showtimes, ratings and seat prices.'
                : ($status ? self::STATUSES[$status][1].' at BookMyMovie cinemas: showtimes, ratings and seat prices.' : 'Browse every film in our cinemas this week and what is coming next. Filter by genre, language and rating, then book seats.'),
            'schema' => [[
                '@type' => 'CollectionPage',
                'name' => $label ? $label.' films' : 'Films in cinemas',
                'url' => Seo::url(request()->getPathInfo()),
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => $movies->count(),
                    'itemListElement' => $movies->values()->map(fn (Movie $movie, $index) => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'url' => Seo::url('movies/'.$movie->slug),
                        'name' => $movie->title,
                    ])->all(),
                ],
            ]],
        ], $breadcrumbs);
    }

    public function show(Request $request, string $slug): Response
    {
        $movie = Movie::query()
            ->withCardMetrics()
            ->with(['genres', 'credits.person'])
            ->where('slug', $slug)
            ->firstOrFail();

        $shows = DB::table('v_show_details')
            ->where('movie_id', $movie->id)
            ->where('show_status', 'scheduled')
            ->whereRaw('TIMESTAMP(show_date, show_time) > ?', [now()->toDateTimeString()])
            ->orderBy('show_date')
            ->orderBy('city')
            ->orderBy('theater_name')
            ->orderBy('show_time')
            ->get();

        $fromPrices = DB::table('show_seat_row_prices')
            ->whereIn('show_id', $shows->pluck('show_id'))
            ->groupBy('show_id')
            ->selectRaw('show_id, MIN(COALESCE(sale_price, price)) AS from_price, MAX(sale_price IS NOT NULL) AS on_sale')
            ->get()
            ->keyBy('show_id');

        $cities = $shows->pluck('city')->unique()->values();

        $showDays = $shows
            ->groupBy('show_date')
            ->map(fn (Collection $day) => $day->groupBy('theater_id')->map(fn (Collection $venue) => [
                'theater' => $venue->first(),
                'shows' => $venue->map(function ($show) use ($fromPrices) {
                    $show->from_price = (float) ($fromPrices[$show->show_id]->from_price ?? 0);
                    $show->on_sale = (bool) ($fromPrices[$show->show_id]->on_sale ?? false);
                    $show->format_label = Screen::FORMAT_LABELS[$show->screen_format] ?? 'Standard 2D';
                    $show->fill_ratio = $show->total_seats > 0 ? $show->booked_seats / $show->total_seats : 0;

                    return $show;
                })->values(),
            ])->values());

        $reviewSummary = ReviewData::summary($movie);

        $similar = Movie::query()
            ->withCardMetrics()
            ->with('genres')
            ->publiclyListed()
            ->whereKeyNot($movie->id)
            ->whereHas('genres', fn (Builder $query) => $query->whereIn('genres.id', $movie->genres->pluck('id')))
            ->orderByDesc('average_rating')
            ->limit(4)
            ->get()
            ->map(fn (Movie $item) => $item->toCardArray());

        $user = Auth::user();
        $canReview = $user && $this->watchedBooking($user->id, $movie->id) !== null;

        $card = $movie->toCardArray();
        $primaryGenre = $movie->genres->first();
        $totalReviews = $reviewSummary['total'];
        $userReview = $user ? Review::query()->where('user_id', $user->id)->where('movie_id', $movie->id)->first() : null;
        $person = fn ($credit) => [
            'name' => $credit->person->name,
            'initials' => $credit->person->initials(),
            'role' => ucfirst($credit->role),
            'character' => $credit->character_name,
        ];

        return $this->page('Movies/Show', [
            'movie' => [
                ...$card,
                'description' => $movie->description,
                'content_advisory' => $movie->content_advisory,
                'certificate_label' => $movie->certificateLabel(),
                'release_long' => $movie->release_date?->format('l, j F Y'),
                'release_short' => $movie->release_date?->format('l, j F'),
                'studio' => $movie->studio ?: 'Independent',
                'country' => $movie->country ?: 'Pakistan',
                'average_rating' => (float) $movie->average_rating,
                'genre_links' => $movie->genres->map(fn ($genre) => ['name' => $genre->name, 'slug' => $genre->slug])->values(),
            ],
            'showDays' => $showDays->map(function (Collection $venues, string $date) {
                $day = Carbon::parse($date);

                return [
                    'date' => $date,
                    'weekday' => $day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tomorrow' : $day->format('D')),
                    'day' => $day->format('j'),
                    'month' => $day->format('M'),
                    'venues' => $venues->map(fn (array $venue) => [
                        'city' => $venue['theater']->city,
                        'name' => $venue['theater']->theater_name,
                        'slug' => $venue['theater']->theater_slug,
                        'address' => $venue['theater']->theater_address,
                        'shows' => $venue['shows']->map(fn ($show) => [
                            'id' => $show->show_id,
                            'time' => Carbon::parse($show->show_time)->format('g:i'),
                            'meridiem' => Carbon::parse($show->show_time)->format('A'),
                            'format' => $show->format_label,
                            'screen' => $show->screen_name,
                            'from_price' => $show->from_price,
                            'on_sale' => $show->on_sale,
                            'fast' => $show->fill_ratio >= 0.6,
                        ])->values(),
                    ])->values(),
                ];
            })->values(),
            'cities' => $cities,
            'showCount' => $shows->count(),
            'venueCount' => $shows->pluck('theater_id')->unique()->count(),
            'reviews' => ReviewData::page($movie, viewerId: $user?->id),
            'reviewSummary' => $reviewSummary,
            'totalReviews' => $totalReviews,
            'distribution' => $reviewSummary['distribution'],
            'pricing' => $this->pricing($movie),
            'similar' => $similar->values(),
            'primaryGenre' => $primaryGenre ? ['name' => $primaryGenre->name, 'slug' => $primaryGenre->slug] : null,
            'directors' => $movie->creditsFor('director')->map($person)->values(),
            'writers' => $movie->creditsFor('writer')->map($person)->values(),
            'crew' => $movie->creditsFor('composer', 'cinematographer', 'editor', 'producer')->map($person)->values(),
            'cast' => $movie->creditsFor('cast')->map($person)->values(),
            'inWishlist' => $user ? Wishlist::query()->where('user_id', $user->id)->where('movie_id', $movie->id)->exists() : false,
            'canReview' => $canReview,
            'userReview' => $userReview ? ['rating' => (int) $userReview->rating, 'title' => (string) $userReview->title, 'text' => $userReview->review_text, 'spoilers' => (bool) $userReview->contains_spoilers, 'photos' => ReviewPhotos::forReview($userReview->load('photos'))] : null,
        ], [
            'title' => $movie->meta_title ?: $movie->title.' | Showtimes & Tickets',
            'description' => $movie->meta_description ?: $movie->tagline.' '.$movie->durationLabel().', '.$movie->certificate_rating.'. Book seats at BookMyMovie.',
            'image' => $movie->ogImageUrl(),
            'image_alt' => 'Poster artwork for '.$movie->title,
            'type' => 'video.movie',
            'schema' => [Seo::movie($movie, $shows)],
        ], array_values(array_filter([
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Films', 'url' => route('movies.index')],
            $primaryGenre ? ['label' => $primaryGenre->name, 'url' => route('movies.genre', $primaryGenre->slug)] : null,
            ['label' => $movie->title, 'url' => null],
        ])));
    }

    /**
     * Verified-booking reviews. A member can review a film only after one of
     * their own bookings for it has actually started (not cancelled, not a
     * no-show); that booking is stored on the review and is what earns the
     * "Verified booking" badge. One review per member per film, editable.
     */
    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $movie = Movie::query()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:90', CleanText::noProfanity('Your headline')],
            'review_text' => ['required', 'string', 'min:20', 'max:1200', CleanText::noProfanity('Your review')],
            'contains_spoilers' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:'.ReviewPhotos::MAX_PER_REVIEW],
            // Raster only (no SVG), real image dimensions, 5 MB each.
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000'],
            'remove_photos' => ['nullable', 'array', 'max:'.ReviewPhotos::MAX_PER_REVIEW],
            'remove_photos.*' => ['integer'],
        ], ['photos.max' => 'Up to '.ReviewPhotos::MAX_PER_REVIEW.' photos per review.', 'photos.*.max' => 'Each photo must be under 5 MB.']);

        $booking = $this->watchedBooking((int) Auth::id(), $movie->id);

        abort_unless($booking, 403, 'Reviews open once you have watched this film with a BookMyMovie booking.');

        $review = Review::query()->updateOrCreate(
            ['user_id' => Auth::id(), 'movie_id' => $movie->id],
            [
                'booking_id' => $booking->id,
                'rating' => $data['rating'],
                'title' => filled($data['title'] ?? null) ? strip_tags($data['title']) : null,
                'contains_spoilers' => (bool) ($data['contains_spoilers'] ?? false),
                'review_text' => strip_tags($data['review_text']),
                'is_approved' => true,
                'is_flagged' => false,
                'approved_at' => now(),
            ]
        );

        // Only the author's own review is touched: remove_photos ids are scoped to it.
        ReviewPhotos::sync($review, $request->file('photos', []), array_map('intval', $data['remove_photos'] ?? []));

        $movie->refreshRating();

        return redirect()->to(route('movies.show', $movie->slug).'#reviews')->with('status', 'Thanks! Your review is live.');
    }

    public function seats(string $slug, int $show): Response
    {
        $movie = Movie::query()->with('genres')->where('slug', $slug)->firstOrFail();

        $showDetails = DB::table('v_show_details')
            ->where('show_id', $show)
            ->where('movie_id', $movie->id)
            ->first();

        abort_unless($showDetails, 404);

        // Guests are sent to sign in before holding seats; bring them back here.
        if (! Auth::check()) {
            session()->put('url.intended', request()->fullUrl());
        }

        $startsAt = Carbon::parse($showDetails->show_date.' '.$showDetails->show_time);
        $onSale = $showDetails->show_status === 'scheduled' && $startsAt->isFuture() && (bool) ($movie->bookings_enabled ?? true);

        $seats = DB::table('v_seat_availability')
            ->where('show_id', $show)
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get();

        $rows = $seats->groupBy('row_label');

        $pricingTiers = $rows
            ->map(fn (Collection $rowSeats, string $label) => [
                'rows' => [$label],
                'tier' => $rowSeats->first()->row_tier_name ?: $rowSeats->first()->category_name,
                'category' => $rowSeats->first()->category_name,
                'benefits' => $rowSeats->first()->row_benefits,
                'price' => (float) $rowSeats->first()->price,
                'sale_price' => $rowSeats->first()->sale_price !== null ? (float) $rowSeats->first()->sale_price : null,
                'kids_price' => $rowSeats->first()->kids_price !== null ? (float) $rowSeats->first()->kids_price : null,
                'kids_sale_price' => $rowSeats->first()->kids_sale_price !== null ? (float) $rowSeats->first()->kids_sale_price : null,
                'available' => $rowSeats->where('seat_status', 'available')->count(),
            ])
            ->values()
            // Merge neighbouring rows that share a tier and price into one line.
            ->reduce(function (Collection $carry, array $tier) {
                $last = $carry->last();

                if ($last && $last['tier'] === $tier['tier'] && $last['price'] === $tier['price']) {
                    $last['rows'][] = $tier['rows'][0];
                    $last['available'] += $tier['available'];

                    return $carry->put($carry->count() - 1, $last);
                }

                return $carry->push($tier);
            }, collect());

        $otherTimes = DB::table('v_show_details')
            ->where('movie_id', $movie->id)
            ->where('theater_id', $showDetails->theater_id)
            ->where('show_date', $showDetails->show_date)
            ->where('show_status', 'scheduled')
            ->orderBy('show_time')
            ->get(['show_id', 'show_time', 'screen_name', 'screen_format', 'available_seats']);

        $seatsPerRow = (int) $rows->max(fn ($row) => $row->count());
        $money = fn ($value) => 'PKR '.number_format((float) $value);
        $summary = $seats->countBy('seat_status');

        return $this->page('Movies/Seats', [
            'movie' => [
                'id' => $movie->id,
                'title' => $movie->title,
                'slug' => $movie->slug,
                'certificate' => $movie->certificate_rating,
                'card' => $movie->toCardArray(0),
            ],
            'show' => [
                'id' => $show,
                'theater' => $showDetails->theater_name,
                'theater_slug' => $showDetails->theater_slug,
                'screen' => $showDetails->screen_name,
                'format' => Screen::FORMAT_LABELS[$showDetails->screen_format] ?? 'Standard 2D',
                'day_label' => $startsAt->isToday() ? 'Tonight' : $startsAt->format('l, j F'),
                'time' => $startsAt->format('g:i A'),
                'starts' => $startsAt->format('D j M, g:i A'),
                'ends' => $startsAt->copy()->addMinutes($movie->duration_minutes + 20)->format('g:i A'),
            ],
            'onSale' => $onSale,
            'waitlist' => [
                'joined' => Auth::check() && DB::table('show_waitlists')->where('show_id', $show)->where('user_id', Auth::id())->exists(),
                'count' => DB::table('show_waitlists')->where('show_id', $show)->whereNull('notified_at')->count(),
            ],
            // Aisles split the room into blocks, as in the real auditorium.
            'aisles' => match (true) {
                $seatsPerRow >= 14 => [4, 10],
                $seatsPerRow >= 12 => [3, 9],
                default => [4],
            },
            'rows' => $rows->map(fn (Collection $rowSeats, string $label) => [
                'label' => $label,
                'seats' => $rowSeats->map(function ($seat) use ($onSale, $money) {
                    $adult = (float) ($seat->sale_price ?? $seat->price);

                    return [
                        'id' => (int) $seat->seat_id,
                        'number' => (int) $seat->seat_number,
                        'label' => $seat->row_label.$seat->seat_number,
                        'tier' => $seat->category_name,
                        'tier_name' => $seat->row_tier_name,
                        'status' => $seat->seat_status,
                        'available' => $onSale && $seat->seat_status === 'available',
                        'adult' => $adult,
                        'kid' => $seat->kids_price !== null ? (float) ($seat->kids_sale_price ?? $seat->kids_price) : null,
                        'price_label' => $money($adult),
                    ];
                })->values(),
            ])->values(),
            'seatSummary' => [
                'available' => (int) ($summary['available'] ?? 0),
                'booked' => (int) ($summary['booked'] ?? 0),
                'reserved' => (int) ($summary['reserved'] ?? 0),
            ],
            'pricingTiers' => $pricingTiers->values(),
            'otherTimes' => $otherTimes->map(fn ($other) => [
                'id' => $other->show_id,
                'time' => Carbon::parse($other->show_time)->format('g:i A'),
                'format' => Screen::FORMAT_LABELS[$other->screen_format] ?? $other->screen_name,
            ])->values(),
            'maxSeats' => (int) config('bookmymovie.booking.max_seats_per_booking', 4),
            'holdMinutes' => (int) config('bookmymovie.booking.cart_hold_minutes', 10),
        ], [
            'title' => 'Seats: '.$movie->title.', '.$startsAt->format('D j M, g:i A'),
            'description' => 'Choose your seats for '.$movie->title.' at '.$showDetails->theater_name.' on '.$startsAt->format('l j F').' at '.$startsAt->format('g:i A').'.',
            'image' => $movie->ogImageUrl(),
        ], [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Films', 'url' => route('movies.index')],
            ['label' => $movie->title, 'url' => route('movies.show', $movie->slug)],
            ['label' => 'Seats', 'url' => null],
        ]);
    }

    /** "Load more", sorting and filters for the film page's reviews. */
    public function reviews(Request $request, string $slug): JsonResponse
    {
        $movie = Movie::query()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'sort' => ['nullable', 'in:'.implode(',', ReviewData::SORTS)],
            'stars' => ['nullable', 'integer', 'between:1,5'],
            'verified' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return response()->json(ReviewData::page(
            $movie,
            $data['sort'] ?? 'recent',
            isset($data['stars']) ? (int) $data['stars'] : null,
            (bool) ($data['verified'] ?? false),
            (int) ($data['page'] ?? 1),
            Auth::id(),
        ));
    }

    /** Toggles the signed-in member's "Helpful" vote on a review. */
    public function voteReview(Review $review): JsonResponse
    {
        $userId = (int) Auth::id();
        abort_if($review->user_id === $userId, 422, 'You cannot vote on your own review.');
        abort_unless($review->is_approved, 404);

        $voted = DB::transaction(function () use ($review, $userId) {
            $removed = DB::table('review_votes')->where('review_id', $review->id)->where('user_id', $userId)->delete();
            if ($removed) {
                Review::query()->whereKey($review->id)->where('helpful_count', '>', 0)->decrement('helpful_count');

                return false;
            }
            // INSERT IGNORE on the (review_id, user_id) key: a double click
            // counts once instead of failing with a duplicate-key error.
            if (DB::table('review_votes')->insertOrIgnore(['review_id' => $review->id, 'user_id' => $userId, 'created_at' => now()]) === 1) {
                Review::query()->whereKey($review->id)->increment('helpful_count');
            }

            return true;
        }, 3);

        return response()->json(['voted' => $voted, 'helpful' => (int) Review::query()->whereKey($review->id)->value('helpful_count')]);
    }

    /** The member's latest booking for this film whose show has already started. */
    private function watchedBooking(int $userId, int $movieId): ?Booking
    {
        return Booking::query()
            ->where('user_id', $userId)
            ->whereIn('booking_status', ['confirmed', 'completed'])
            ->whereHas('show', fn (Builder $query) => $query
                ->where('movie_id', $movieId)
                ->whereRaw('TIMESTAMP(show_date, show_time) <= ?', [now()->toDateTimeString()]))
            ->latest('booked_at')
            ->first();
    }

    /**
     * Ticket prices across this film's upcoming shows, per seating tier:
     * the lowest and highest adult price, the child price and what the tier
     * includes. Sale prices are used where set.
     *
     * @return list<array{tier: string, rows: string, from: float, to: float, was: ?float, kids: ?float, benefits: ?string}>
     */
    private function pricing(Movie $movie): array
    {
        return DB::table('show_seat_row_prices as p')
            ->join('shows as s', 's.id', '=', 'p.show_id')
            ->where('s.movie_id', $movie->id)
            ->where('s.status', 'scheduled')
            ->where('s.show_date', '>=', now()->toDateString())
            ->groupBy('p.tier_name')
            ->selectRaw("p.tier_name AS tier, GROUP_CONCAT(DISTINCT p.row_label ORDER BY p.row_label SEPARATOR '') AS `rows`,
                MIN(COALESCE(p.sale_price, p.price)) AS low, MAX(COALESCE(p.sale_price, p.price)) AS high,
                MAX(CASE WHEN p.sale_price IS NOT NULL AND p.sale_price < p.price THEN p.price END) AS was,
                MIN(COALESCE(p.kids_sale_price, p.kids_price)) AS kids, MAX(p.benefits) AS benefits")
            ->orderByDesc('low')
            ->get()
            ->map(fn ($tier) => [
                'tier' => $tier->tier,
                'rows' => implode(', ', str_split((string) $tier->rows)),
                'from' => (float) $tier->low,
                'to' => (float) $tier->high,
                'was' => $tier->was !== null ? (float) $tier->was : null,
                'kids' => $movie->kids_discount_eligible && $tier->kids !== null ? (float) $tier->kids : null,
                'benefits' => $tier->benefits,
            ])
            ->values()
            ->all();
    }
}
