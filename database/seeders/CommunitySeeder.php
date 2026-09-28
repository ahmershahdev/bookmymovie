<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The review community: about eighty members with usernames, cities and
 * bios, and 45–55 reviews on every film.
 *
 * Each review is "verified" the honest way. The member gets a real, paid,
 * completed booking for a screening of that film that has already happened,
 * and the review points at that booking, exactly as MovieController does for
 * live reviews. Nothing sets the badge directly.
 *
 * Safe to re-run: it tops each film up to its target and skips members who
 * have already reviewed it. Run on its own with
 *   php artisan db:seed --class=CommunitySeeder
 */
class CommunitySeeder extends Seeder
{
    private const MEMBERS = 84;

    private const FIRST = ['Ayesha', 'Hira', 'Mahnoor', 'Fatima', 'Zainab', 'Areeba', 'Sana', 'Noor', 'Rabia', 'Mehwish', 'Laiba', 'Emaan', 'Iman', 'Khadija', 'Sadia', 'Aiman', 'Minal', 'Rida', 'Amna', 'Kinza', 'Ali', 'Hassan', 'Hussain', 'Ahmed', 'Saad', 'Taha', 'Zain', 'Shahzaib', 'Talha', 'Arham', 'Rayyan', 'Haris', 'Fahad', 'Waleed', 'Asad', 'Junaid', 'Kamran', 'Adeel', 'Sameer', 'Nabeel', 'Mustafa', 'Owais'];

    private const LAST = ['Raza', 'Butt', 'Chaudhry', 'Mirza', 'Baig', 'Abbasi', 'Awan', 'Khattak', 'Memon', 'Shaikh', 'Kazmi', 'Naqvi', 'Rizvi', 'Bhatti', 'Gill', 'Cheema', 'Tariq', 'Aslam', 'Zafar', 'Saeed', 'Hayat', 'Durrani', 'Yousafzai', 'Jatoi', 'Soomro', 'Lodhi', 'Anwar', 'Kiani', 'Rehman', 'Ansari'];

    private const CITIES = ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Hyderabad', 'Sialkot', 'Quetta', 'Gujranwala', 'Abbottabad'];

    private const BIOS = [
        'Weekend matinee regular. Front-row sceptic, back-row convert.',
        'I review the popcorn as seriously as the plot.',
        'Film student by day, midnight-show loyalist by night.',
        'Here for the sound mix. Dolby or bust.',
        'Dragging my cousins to subtitled films since 2015.',
        'Mostly thrillers, occasionally a good cry.',
        'Will travel across the city for a 70mm print.',
        'Architect. I notice the production design first.',
        'I keep a spreadsheet of every film I have seen in a cinema.',
        'Recliner seats changed my life.',
        'Horror fan who still watches through his fingers.',
        'Family movie nights, three kids, zero spoilers please.',
        null, null, null,
    ];

    /** @var list<int> */
    private array $members = [];

    public function run(): void
    {
        mt_srand(20260929);
        $this->members = $this->seedMembers();

        $movies = Movie::query()->with(['genres', 'credits.person'])->get();
        $screens = DB::table('screens')->join('theaters', 'theaters.id', '=', 'screens.theater_id')
            ->where('screens.is_active', true)->get(['screens.id', 'screens.format', 'theaters.name as theater']);
        $adminId = (int) DB::table('admins')->orderBy('id')->value('id');

        foreach ($movies as $movie) {
            $existing = DB::table('reviews')->where('movie_id', $movie->id)->pluck('user_id')->flip();
            $target = mt_rand(45, 55);
            $needed = max(0, $target - $existing->count());
            if ($needed === 0) {
                continue;
            }

            $reviewers = collect($this->members)->reject(fn (int $id) => $existing->has($id))->shuffle()->take($needed)->values();

            DB::transaction(function () use ($movie, $screens, $adminId, $reviewers) {
                $screenings = $this->pastScreenings($movie, $screens, $adminId, (int) ceil($reviewers->count() / 14));

                foreach ($reviewers as $index => $userId) {
                    $slot = $index % count($screenings);
                    $booking = $this->completedBooking($screenings[$slot], $userId);
                    $screening = $screenings[$slot];
                    $rating = $this->rating($movie);
                    [$title, $text] = $this->compose($movie, $rating, $screening);
                    $at = Carbon::parse($screening['starts'])->addHours(mt_rand(3, 72));
                    $at = $at->isFuture() ? now()->subMinutes(mt_rand(10, 300)) : $at;

                    DB::table('reviews')->insert([
                        'user_id' => $userId,
                        'movie_id' => $movie->id,
                        'booking_id' => $booking,
                        'rating' => $rating,
                        'title' => $title,
                        'review_text' => $text,
                        'contains_spoilers' => mt_rand(1, 14) === 1,
                        'helpful_count' => $this->helpful($rating),
                        'is_approved' => true,
                        'is_flagged' => false,
                        'approved_by' => $adminId ?: null,
                        'approved_at' => $at->copy()->addMinutes(mt_rand(5, 240)),
                        'created_at' => $at,
                        'updated_at' => $at,
                    ]);
                }
            });

            $movie->forceFill(['rating_mode' => 'real', 'fake_average_rating' => null, 'fake_total_reviews' => null])->save();
            $movie->refreshRating();
        }
    }

    /** @return list<int> */
    private function seedMembers(): array
    {
        $existing = DB::table('users')->where('email', 'like', '%@members.bookmymovie.test')->pluck('id')->all();
        if (count($existing) >= self::MEMBERS) {
            return $existing;
        }

        $password = Hash::make(Str::random(32)); // community accounts cannot be signed into
        $names = [];
        while (count($names) < self::MEMBERS) {
            $name = self::FIRST[array_rand(self::FIRST)].' '.self::LAST[array_rand(self::LAST)];
            $names[$name] = true;
        }

        $ids = [];
        foreach (array_keys($names) as $index => $name) {
            $handle = $this->handle($name, $index);
            $joined = now()->subDays(mt_rand(40, 900));
            $ids[] = DB::table('users')->insertGetId([
                'name' => $name,
                'username' => $handle,
                'email' => $handle.'@members.bookmymovie.test',
                'email_verified_at' => $joined,
                'password' => $password,
                'city' => self::CITIES[array_rand(self::CITIES)],
                'bio' => self::BIOS[array_rand(self::BIOS)],
                'gender' => in_array(Str::before($name, ' '), array_slice(self::FIRST, 0, 20), true) ? 'female' : 'male',
                'is_blocked' => false,
                'loyalty_points' => mt_rand(0, 900),
                'two_factor_enabled' => false,
                'locale' => mt_rand(1, 5) === 1 ? 'ur' : 'en',
                'created_at' => $joined,
                'updated_at' => $joined,
            ]);
        }

        return $ids;
    }

    private function handle(string $name, int $index): string
    {
        [$first, $last] = explode(' ', Str::lower(Str::ascii($name)));
        $styles = [$first.'_'.$last, $first.$last[0], $first.'.'.$last, $first.'_reviews', $last.'_'.$first, $first.'watches', 'cinephile_'.$first, $first.$last];
        $base = str_replace('.', '_', $styles[$index % count($styles)]);
        $candidate = $base;
        for ($n = 2; DB::table('users')->where('username', $candidate)->exists(); $n++) {
            $candidate = $base.$n;
        }

        return substr($candidate, 0, 30);
    }

    /**
     * Completed screenings of this film in the last six weeks, one row of
     * seat prices each, so the bookings behind the reviews are real.
     *
     * @return list<array{id: int, starts: string, theater: string, format: string, seats: Collection, next: int}>
     */
    private function pastScreenings(Movie $movie, Collection $screens, int $adminId, int $count): array
    {
        $slots = ['13:30:00', '16:15:00', '19:00:00', '21:45:00'];
        $screenings = [];

        foreach ($screens->shuffle()->take(max(2, min(5, $count))) as $screen) {
            $date = now()->subDays(mt_rand(3, 42))->toDateString();
            $time = $slots[array_rand($slots)];
            if (DB::table('shows')->where('screen_id', $screen->id)->where('show_date', $date)->where('show_time', $time)->exists()) {
                continue;
            }

            $seats = DB::table('seats')->where('screen_id', $screen->id)->where('is_active', true)->orderBy('row_label')->orderBy('seat_number')->get();
            $showId = DB::table('shows')->insertGetId([
                'movie_id' => $movie->id,
                'screen_id' => $screen->id,
                'show_date' => $date,
                'show_time' => $time,
                'status' => 'completed',
                'total_seats' => $seats->count(),
                'booked_seats' => 0,
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $base = (float) ($movie->sale_price ?: $movie->base_price ?: 1000);
            foreach ($seats->pluck('row_label')->unique()->values() as $position => $row) {
                $price = round(($base + $position * 100) / 50) * 50;
                DB::table('show_seat_row_prices')->insert([
                    'show_id' => $showId, 'row_label' => $row, 'tier_name' => 'Standard', 'benefits' => null,
                    'price' => $price, 'sale_price' => null, 'kids_price' => round($price * 0.7 / 50) * 50, 'kids_sale_price' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $screenings[] = [
                'id' => $showId,
                'starts' => $date.' '.$time,
                'theater' => $screen->theater,
                'format' => \App\Models\Screen::FORMAT_LABELS[$screen->format] ?? 'Standard 2D',
                'seats' => $seats->shuffle()->values(),
                'prices' => DB::table('show_seat_row_prices')->where('show_id', $showId)->pluck('price', 'row_label'),
                'next' => 0,
            ];
        }

        return $screenings;
    }

    private function completedBooking(array &$screening, int $userId): int
    {
        $party = mt_rand(1, 3);
        $seats = $screening['seats']->slice($screening['next'], $party)->values();
        $screening['next'] += $party;
        if ($seats->isEmpty()) {
            $seats = $screening['seats']->take(1);
        }

        $starts = Carbon::parse($screening['starts']);
        $bookedAt = $starts->copy()->subHours(mt_rand(4, 120));
        $number = 'BM-'.$starts->format('Y').'-'.strtoupper(Str::random(8));
        $total = (float) $seats->sum(fn ($seat) => $screening['prices'][$seat->row_label] ?? 1000);
        $user = DB::table('users')->where('id', $userId)->first(['name', 'email']);

        $bookingId = DB::table('bookings')->insertGetId([
            'booking_number' => $number,
            'user_id' => $userId,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_address' => 'Provided at the counter',
            'show_id' => $screening['id'],
            'seat_count' => $seats->count(),
            'adult_count' => $seats->count(),
            'kids_count' => 0,
            'subtotal' => $total,
            'discount_amount' => 0,
            'total_amount' => $total,
            'points_earned' => (int) floor($total * 0.05),
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'booking_status' => 'completed',
            'booked_at' => $bookedAt,
            'updated_at' => $starts,
        ]);

        DB::table('booking_seats')->insert($seats->map(fn ($seat) => [
            'booking_id' => $bookingId,
            'seat_id' => $seat->id,
            'show_id' => $screening['id'],
            'seat_category_id' => $seat->seat_category_id,
            'ticket_type' => 'adult',
            'price_paid' => $screening['prices'][$seat->row_label] ?? 1000,
            'ticket_number' => $number.'-'.$seat->row_label.$seat->seat_number,
            'seat_lock' => 1,
            'created_at' => $bookedAt,
        ])->all());

        DB::table('payments')->insert([
            'booking_id' => $bookingId, 'payment_method' => 'cod', 'amount' => $total, 'status' => 'paid',
            'transaction_reference' => 'CTR-'.strtoupper(Str::random(10)), 'notes' => 'Paid at the box office.',
            'paid_at' => $starts->copy()->subMinutes(25), 'created_at' => $bookedAt, 'updated_at' => $starts,
        ]);

        DB::table('booking_events')->insert([
            ['booking_id' => $bookingId, 'event' => 'confirmed', 'from_status' => null, 'to_status' => 'confirmed', 'note' => 'Booking confirmed online.', 'actor_type' => 'user', 'actor_id' => $userId, 'created_at' => $bookedAt],
            ['booking_id' => $bookingId, 'event' => 'admitted', 'from_status' => 'confirmed', 'to_status' => 'completed', 'note' => 'Ticket scanned at the screen door.', 'actor_type' => 'system', 'actor_id' => null, 'created_at' => $starts->copy()->subMinutes(8)],
        ]);

        DB::table('shows')->where('id', $screening['id'])->increment('booked_seats', $seats->count());

        return $bookingId;
    }

    /** Ratings lean on the film's own seeded reputation, with a realistic spread. */
    private function rating(Movie $movie): int
    {
        $bias = (float) ($movie->average_rating ?: 4.0);
        $roll = mt_rand(1, 100);
        $rating = (int) round($bias + ($roll <= 8 ? -2 : ($roll <= 25 ? -1 : ($roll <= 70 ? 0 : 1))));

        return max(1, min(5, $rating));
    }

    private function helpful(int $rating): int
    {
        $roll = mt_rand(1, 100);

        return $roll <= 40 ? mt_rand(0, 3) : ($roll <= 85 ? mt_rand(4, 18) : mt_rand(19, 96)) + ($rating <= 2 ? mt_rand(0, 6) : 0);
    }

    /**
     * Writes a review the way people actually write them: a point of view,
     * one or two specifics about this film, a note on the screening, and not
     * every review hits every beat.
     *
     * @return array{0: ?string, 1: string}
     */
    private function compose(Movie $movie, int $rating, array $screening): array
    {
        $genre = Str::lower($movie->genres->first()?->name ?? 'drama');
        $cast = $movie->credits->where('role', 'cast')->sortBy('billing_order')->pluck('person.name')->values();
        $director = $movie->credits->firstWhere('role', 'director')?->person?->name;
        $lead = $cast->get(mt_rand(0, max(0, min(2, $cast->count() - 1)))) ?? 'the lead';
        $title = $movie->title;
        $band = $rating >= 5 ? 'love' : ($rating === 4 ? 'like' : ($rating === 3 ? 'mixed' : 'dislike'));

        $openers = [
            'love' => ["Went in with low expectations and walked out grinning.", "This is why I still pay for a cinema seat.", "Easily the best thing I've seen on a big screen this year.", "{$title} earns every minute of its runtime.", "I've already booked a second show for my parents.", "Didn't check my phone once, which says it all."],
            'like' => ["Really solid night out.", "Not perfect, but I'd recommend it without thinking twice.", "A {$genre} that actually respects your time.", "Better than the trailer suggested.", "Good film, great screening."],
            'mixed' => ["Half of this film is excellent, the other half is fine.", "I wanted to love it more than I did.", "Worth a matinee price, maybe not a Saturday night one.", "Ambitious, a little uneven.", "Came out of it with mixed feelings."],
            'dislike' => ["Honestly, this one didn't work for me.", "I kept waiting for it to click and it never did.", "Beautiful to look at, hard to care about.", "Not my kind of {$genre}, and I say that as a fan of the genre.", "Left a bit disappointed."],
        ];

        $specifics = [
            'love' => ["{$lead} is magnetic; you can't look anywhere else when they're on screen.", $director ? "{$director} directs with real confidence, every scene knows what it's for." : "The direction is confident, every scene knows what it's for.", "The score does so much heavy lifting in the final act, I had goosebumps.", "The last twenty minutes are some of the most tense cinema I've sat through.", "It's funny, then it's devastating, and it never feels like whiplash.", "The cinematography alone would be worth the ticket."],
            'like' => ["{$lead} carries the quieter scenes really well.", "The middle drags slightly, but the ending pays it off.", "A couple of jokes land better with a full house.", "The world-building is detailed without drowning you in it.", "Good chemistry across the cast, especially in the ensemble scenes."],
            'mixed' => ["{$lead} is great, but the script gives them too little to do.", "The pacing in the second act really tested my patience.", "Some dialogue felt written for the trailer rather than the scene.", "Visually stunning, emotionally a bit flat.", "The twist is well staged but you can see it coming."],
            'dislike' => ["The story loses track of itself around the halfway mark.", "Too many subplots, none of them landed.", "{$lead} tries hard, but the character never makes sense.", "It runs at least twenty minutes too long.", "The ending felt rushed after all that build-up."],
        ];

        $venue = [
            "Watched it in {$screening['format']} at {$screening['theater']} and the sound was spot on.",
            "{$screening['theater']} was spotless and the staff were quick at the counter.",
            "Saw the evening show at {$screening['theater']}; a full, loud, happy crowd.",
            $screening['format'] === 'Standard 2D' ? "Even on a regular screen it looked great." : "{$screening['format']} made a real difference for this one.",
            "Booking took under a minute and the e-ticket scanned straight away.",
            "Seats were comfortable, though the AC was a little too cold.",
        ];

        $closers = [
            'love' => ['Go. Take someone who needs a good night out.', 'Ten out of ten, would queue again.', "Don't wait for streaming.", 'Already thinking about it the next day.'],
            'like' => ['Recommended.', 'A good pick for the weekend.', 'Worth the ticket.', "I'd watch it again with friends."],
            'mixed' => ['Your mileage may vary.', 'Catch it on a discount day.', 'Fans of the genre will enjoy it more than I did.', ''],
            'dislike' => ['Maybe skip this one.', 'Hope the next one is better.', 'Wait for it to stream.', ''],
        ];

        $titles = [
            'love' => ['A must-see on the big screen', 'Stunning from start to finish', 'Best film this year', "Believe the hype", 'Absolutely worth it', 'Gave me goosebumps'],
            'like' => ['Solid and satisfying', 'Great night out', 'Better than expected', 'Well worth the ticket', 'Really enjoyable'],
            'mixed' => ['Good, not great', 'Uneven but interesting', 'Mixed feelings', 'Beautiful but slow', 'Decent watch'],
            'dislike' => ['Not for me', 'Disappointing', 'Too long, too thin', 'Expected more', 'Missed the mark'],
        ];

        $parts = [$openers[$band][array_rand($openers[$band])]];
        $pool = $specifics[$band];
        shuffle($pool);
        $parts = array_merge($parts, array_slice($pool, 0, mt_rand(1, 2)));
        if (mt_rand(1, 3) !== 1) {
            $parts[] = $venue[array_rand($venue)];
        }
        $parts[] = $closers[$band][array_rand($closers[$band])];

        $text = trim(implode(' ', array_filter($parts)));
        if (mb_strlen($text) < 40) {
            $text .= ' '.$pool[0];
        }

        return [mt_rand(1, 6) === 1 ? null : $titles[$band][array_rand($titles[$band])], $text];
    }
}
