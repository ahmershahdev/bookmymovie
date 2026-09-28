<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\ContentPage;
use App\Models\Movie;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\MovieMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Builds a complete, realistic demo: a six-venue cinema network, 24 original
 * films with cast and crew, a week of showtimes with row-tier pricing, real
 * reviews and live occupancy created through proper bookings.
 *
 * Every run truncates the domain tables, so it refuses to run in production
 * unless SEED_ALLOW_PRODUCTION=true is set explicitly.
 */
class DatabaseSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@bookmymovie.test';

    public const ADMIN_PASSWORD = 'AdminReset@2026!';

    public const DEMO_EMAIL = 'test@example.com';

    public const DEMO_PASSWORD = 'Password@123';

    private const SLOTS = ['11:00:00', '14:00:00', '17:00:00', '20:00:00', '22:45:00'];

    private const PREMIUM_FORMATS = ['imax', 'dolby_cinema', '4dx', 'screenx'];

    private const FORMAT_MULTIPLIER = [
        'standard' => 1.0,
        'screenx' => 1.2,
        'dolby_cinema' => 1.3,
        'imax' => 1.45,
        '4dx' => 1.55,
        'recliner' => 1.0,
    ];

    /** @var array<string, array{0: string, 1: int, 2: string}> tier => [category, base price, benefits] */
    private const TIERS = [
        'Front Stalls' => ['Gold', 1100, 'Closest to the screen and the most affordable seats in the house.'],
        'Stalls' => ['Gold', 1250, 'Wide view with easy aisle access, a favourite for action films.'],
        'Classic' => ['Gold', 1450, 'Comfortable padded seating with a clear, full-screen view.'],
        'Prime' => ['Platinum', 1750, 'Raised rows with extra legroom and centred surround sound.'],
        'Prime Centre' => ['Platinum', 1950, 'The sweet spot: eye level with the screen and dead centre of the mix.'],
        'Recliner' => ['Box', 2600, 'Powered leather recliner, side table and blanket on request.'],
        'Recliner Lounge' => ['Box', 2900, 'Back-row recliner pairs with in-seat service and priority entry.'],
    ];

    private int $adminId;

    /** @var array<string, int> */
    private array $genreIds = [];

    /** @var array<string, int> */
    private array $peopleIds = [];

    /** @var list<int> */
    private array $customerIds = [];

    private int $demoUserId;

    public function run(): void
    {
        if (app()->isProduction() && ! filter_var(env('SEED_ALLOW_PRODUCTION', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Refusing to seed demo data in production. Set SEED_ALLOW_PRODUCTION=true if you really mean it.');
        }

        mt_srand(2026);

        $this->truncateDomainTables();
        $this->seedAdmins();
        $this->seedUsers();
        $this->seedGenres();
        $categories = $this->seedSeatCategories();
        $screens = $this->seedCinemaNetwork($categories);
        $movies = $this->seedMovies();
        MovieMedia::attach();
        $shows = $this->seedShowtimes($screens, $movies);
        $this->seedPricing($shows, $categories);
        $this->seedReviews($movies);
        $this->seedBookings($shows);
        $this->seedCoupons();
        $this->seedSiteSettings();
        $this->seedContentPages();
        $this->seedFaqs();
        $this->seedInboxAndNotifications();
        $this->call([DemoAdminSeeder::class, CommunitySeeder::class]);
    }

    private function truncateDomainTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'audit_logs', 'loyalty_transactions', 'booking_concessions', 'gift_cards',
            'security_events', 'booking_events', 'contact_messages', 'faqs', 'content_pages', 'site_settings',
            'admin_activity_logs', 'admin_notifications', 'notifications', 'wishlists', 'reviews', 'coupon_usages',
            'payments', 'booking_seats', 'bookings', 'cart_items', 'carts', 'coupons', 'show_seat_row_prices',
            'show_seat_prices', 'shows', 'seats', 'seat_categories', 'screens', 'theater_amenity', 'amenities',
            'theaters', 'cities', 'movie_credits', 'people', 'movie_genres', 'movies', 'genres', 'users', 'admins',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    private function seedAdmins(): void
    {
        $this->adminId = Admin::create([
            'name' => 'Box Office Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => Hash::make(self::ADMIN_PASSWORD),
            'role' => 'superadmin',
            'is_active' => true,
        ])->id;

        Admin::create([
            'name' => 'Programming Editor',
            'email' => 'content@bookmymovie.test',
            'password' => Hash::make(self::ADMIN_PASSWORD),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function seedUsers(): void
    {
        $password = Hash::make(self::DEMO_PASSWORD);

        $this->demoUserId = User::forceCreate([
            'name' => 'Ayaan Sheikh',
            'email' => self::DEMO_EMAIL,
            'email_verified_at' => now(),
            'password' => $password,
            'phone' => '0300 1234567',
            'address' => 'House 14, Street 7, F-7/2, Islamabad',
            'date_of_birth' => '1997-03-14',
            'gender' => 'male',
        ])->id;

        $customers = [
            ['Maryam Qureshi', 'female'], ['Daniyal Khan', 'male'], ['Sara Ahmed', 'female'], ['Bilal Rana', 'male'],
            ['Hania Malik', 'female'], ['Omer Farooq', 'male'], ['Zoya Hashmi', 'female'], ['Hamza Iqbal', 'male'],
            ['Anaya Siddiqui', 'female'], ['Faraz Haider', 'male'], ['Iqra Nadeem', 'female'], ['Usman Javed', 'male'],
        ];

        foreach ($customers as $index => [$name, $gender]) {
            $this->customerIds[] = User::forceCreate([
                'name' => $name,
                'email' => Str::slug($name, '.').'@example.com',
                'email_verified_at' => now()->subDays(30 + $index),
                'password' => $password,
                'phone' => sprintf('03%02d %07d', 10 + $index, 1000000 + $index * 7919),
                'gender' => $gender,
                'created_at' => now()->subDays(60 - $index * 3),
            ])->id;
        }
    }

    private function seedGenres(): void
    {
        foreach ([
            'action' => 'Action', 'adventure' => 'Adventure', 'animation' => 'Animation', 'comedy' => 'Comedy',
            'crime' => 'Crime', 'drama' => 'Drama', 'family' => 'Family', 'fantasy' => 'Fantasy',
            'historical' => 'Historical', 'horror' => 'Horror', 'mystery' => 'Mystery', 'romance' => 'Romance',
            'science-fiction' => 'Science Fiction', 'thriller' => 'Thriller',
        ] as $slug => $name) {
            $this->genreIds[$slug] = DB::table('genres')->insertGetId(['name' => $name, 'slug' => $slug, 'created_at' => now()]);
        }
    }

    /**
     * @return array<string, int> category name => id
     */
    private function seedSeatCategories(): array
    {
        $ids = [];

        foreach ([
            'Gold' => 'Classic seating: front and middle rows with standard comfort.',
            'Platinum' => 'Prime seating: raised centre rows with extra legroom.',
            'Box' => 'Recliners: powered leather seats in the back rows and lounge screens.',
        ] as $name => $description) {
            $ids[$name] = DB::table('seat_categories')->insertGetId(['name' => $name, 'description' => $description, 'created_at' => now()]);
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $categories
     * @return list<array{id: int, format: string, rows: array<string, string>, seats: int}>
     */
    private function seedCinemaNetwork(array $categories): array
    {
        $data = require __DIR__.'/data/cinemas.php';

        $cityIds = [];
        foreach ($data['cities'] as $city) {
            $cityIds[$city['name']] = DB::table('cities')->insertGetId([
                'name' => $city['name'],
                'slug' => Str::slug($city['name']),
                'province' => $city['province'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $amenityIds = [];
        foreach ($data['amenities'] as [$name, $description]) {
            $amenityIds[$name] = DB::table('amenities')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
            ]);
        }

        $screens = [];

        foreach ($data['theaters'] as $theater) {
            $theaterId = DB::table('theaters')->insertGetId([
                'name' => $theater['name'],
                'slug' => Str::slug($theater['name']),
                'address' => $theater['address'],
                'city_id' => $cityIds[$theater['city']],
                'phone' => $theater['phone'],
                'email' => $theater['email'],
                'description' => $theater['description'],
                'latitude' => $theater['lat'],
                'longitude' => $theater['lng'],
                'opens_at' => $theater['opens'],
                'closes_at' => $theater['closes'],
                'is_active' => true,
                'created_by' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('theater_amenity')->insert(array_map(
                fn (string $amenity) => ['theater_id' => $theaterId, 'amenity_id' => $amenityIds[$amenity]],
                $theater['amenities']
            ));

            foreach ($theater['screens'] as [$screenName, $format, $sound]) {
                $rows = $this->rowTiersFor($format);
                $seatsPerRow = match ($format) {
                    'imax' => 14,
                    'recliner' => 8,
                    default => 12,
                };

                $screenId = DB::table('screens')->insertGetId([
                    'theater_id' => $theaterId,
                    'screen_name' => $screenName,
                    'format' => $format,
                    'sound_system' => $sound,
                    'is_wheelchair_accessible' => true,
                    'total_seats' => count($rows) * $seatsPerRow,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $seatRows = [];
                foreach ($rows as $rowLabel => $tier) {
                    for ($number = 1; $number <= $seatsPerRow; $number++) {
                        $seatRows[] = [
                            'screen_id' => $screenId,
                            'seat_category_id' => $categories[self::TIERS[$tier][0]],
                            'row_label' => $rowLabel,
                            'seat_number' => $number,
                            'is_active' => true,
                            'created_at' => now(),
                        ];
                    }
                }
                DB::table('seats')->insert($seatRows);

                $screens[] = ['id' => $screenId, 'format' => $format, 'rows' => $rows, 'seats' => count($seatRows)];
            }
        }

        return $screens;
    }

    /**
     * Front rows are cheapest, the centre rows have the best sightlines and
     * the back rows are recliners, as in a real auditorium.
     *
     * @return array<string, string> row label => tier name
     */
    private function rowTiersFor(string $format): array
    {
        if ($format === 'recliner') {
            return ['A' => 'Recliner', 'B' => 'Recliner', 'C' => 'Recliner', 'D' => 'Recliner Lounge', 'E' => 'Recliner Lounge'];
        }

        $labels = $format === 'imax' ? range('A', 'J') : range('A', 'H');
        $count = count($labels);
        $tiers = [];

        foreach ($labels as $index => $label) {
            $tiers[$label] = match (true) {
                $index === 0 => 'Front Stalls',
                $index === 1 => 'Stalls',
                $index === 2 => 'Classic',
                $index === $count - 1 => 'Recliner Lounge',
                $index === $count - 2 => 'Recliner',
                in_array($index, [intdiv($count, 2) - 1, intdiv($count, 2)], true) => 'Prime Centre',
                default => 'Prime',
            };
        }

        return $tiers;
    }

    /**
     * @return array<string, array{id: int, status: string, genres: list<string>, duration: int}>
     */
    private function seedMovies(): array
    {
        $catalogue = require __DIR__.'/data/movies.php';
        $movies = [];

        foreach ($catalogue as $index => $film) {
            $slug = Str::slug(str_replace('’', '', $film['title']));

            $movie = Movie::create([
                'title' => $film['title'],
                'tagline' => $film['tagline'],
                'slug' => $slug,
                'description' => $film['description'],
                'language' => $film['language'],
                'studio' => $film['studio'],
                'country' => $film['country'],
                'duration_minutes' => $film['duration'],
                'certificate_rating' => $film['certificate'],
                'content_advisory' => $film['advisory'],
                'release_date' => now()->addDays($film['release'])->toDateString(),
                'status' => $film['status'],
                'hero_carousel_enabled' => $film['hero'] !== null,
                'hero_sort_order' => $index,
                'hero_eyebrow' => $film['hero'],
                'hero_tagline' => $film['tagline'],
                'meta_title' => Str::limit($film['title'].' | Showtimes & Tickets', 60, ''),
                'meta_description' => Str::limit($film['tagline'].' Book '.$film['title'].' seats online at BookMyMovie.', 150, ''),
                'base_price' => 1750,
                'sale_price' => 1500,
                'kids_discount_eligible' => in_array($film['certificate'], ['G', 'PG', 'U'], true),
                'rating_mode' => 'real',
                'created_by' => $this->adminId,
            ]);

            DB::table('movie_genres')->insert(array_map(
                fn (string $genre) => ['movie_id' => $movie->id, 'genre_id' => $this->genreIds[$genre]],
                $film['genres']
            ));

            $credits = [];
            $order = 0;
            foreach (['director', 'writer', 'composer', 'cinematographer'] as $role) {
                $credits[] = ['movie_id' => $movie->id, 'person_id' => $this->person($film[$role], $role), 'role' => $role, 'character_name' => null, 'billing_order' => $order++];
            }
            foreach ($film['cast'] as [$actor, $character]) {
                $credits[] = ['movie_id' => $movie->id, 'person_id' => $this->person($actor, 'acting'), 'role' => 'cast', 'character_name' => $character, 'billing_order' => $order++];
            }
            // A writer-director gets one row per role; the unique key is (movie, person, role).
            DB::table('movie_credits')->insert($credits);

            $movies[$slug] = [
                'id' => $movie->id,
                'status' => $film['status'],
                'genres' => $film['genres'],
                'duration' => $film['duration'],
                'reviews' => $film['reviews'],
            ];
        }

        return $movies;
    }

    private function person(string $name, string $knownFor): int
    {
        if (! isset($this->peopleIds[$name])) {
            $this->peopleIds[$name] = DB::table('people')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name),
                'known_for' => ucfirst($knownFor),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->peopleIds[$name];
    }

    /**
     * One week of shows. Premium formats run the spectacle-led titles; every
     * other screen rotates the full now-showing slate.
     *
     * @param  list<array{id: int, format: string, rows: array<string, string>, seats: int}>  $screens
     * @param  array<string, array{id: int, status: string, genres: list<string>, duration: int}>  $movies
     * @return list<array{id: int, screen: array, date: string, time: string, status: string}>
     */
    private function seedShowtimes(array $screens, array $movies): array
    {
        $nowShowing = array_filter($movies, fn (array $movie) => $movie['status'] === 'now_showing');
        $spectacle = array_filter($nowShowing, fn (array $movie) => array_intersect($movie['genres'], ['action', 'adventure', 'science-fiction', 'thriller', 'horror', 'historical']) !== []);
        $nowShowing = array_values($nowShowing);
        $spectacle = array_values($spectacle);

        $shows = [];
        $rows = [];

        foreach ($screens as $screenIndex => $screen) {
            $programme = in_array($screen['format'], self::PREMIUM_FORMATS, true) ? $spectacle : $nowShowing;

            for ($day = 0; $day < 7; $day++) {
                $date = now()->startOfDay()->addDays($day);

                foreach (self::SLOTS as $slotIndex => $time) {
                    $movie = $programme[($screenIndex * 3 + $day * 2 + $slotIndex) % count($programme)];
                    $startsAt = Carbon::parse($date->toDateString().' '.$time);

                    $rows[] = [
                        'movie_id' => $movie['id'],
                        'screen_id' => $screen['id'],
                        'show_date' => $date->toDateString(),
                        'show_time' => $time,
                        'status' => $startsAt->isPast() ? 'completed' : 'scheduled',
                        'total_seats' => $screen['seats'],
                        'booked_seats' => 0,
                        'created_by' => $this->adminId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('shows')->insert($chunk);
        }

        $screenById = collect($screens)->keyBy('id');

        foreach (DB::table('shows')->orderBy('id')->get() as $show) {
            $shows[] = [
                'id' => $show->id,
                'screen' => $screenById[$show->screen_id],
                'date' => $show->show_date,
                'time' => $show->show_time,
                'status' => $show->status,
            ];
        }

        return $shows;
    }

    /**
     * Row-tier prices per show, plus a per-category fallback price. Weekday
     * shows before 5 pm carry a matinée sale price.
     *
     * @param  list<array{id: int, screen: array, date: string, time: string, status: string}>  $shows
     * @param  array<string, int>  $categories
     */
    private function seedPricing(array $shows, array $categories): void
    {
        $rowPrices = [];
        $categoryPrices = [];

        foreach ($shows as $show) {
            $multiplier = self::FORMAT_MULTIPLIER[$show['screen']['format']];
            $date = Carbon::parse($show['date']);
            $isMatinee = ! $date->isWeekend() && $show['time'] < '17:00:00';
            $perCategory = [];

            foreach ($show['screen']['rows'] as $rowLabel => $tier) {
                [$category, $base, $benefits] = self::TIERS[$tier];
                $price = $this->roundPrice($base * $multiplier);
                $sale = $isMatinee ? $this->roundPrice($price * 0.85) : null;
                $kids = $category === 'Box' ? null : $this->roundPrice($price * 0.75);
                $kidsSale = $kids !== null && $isMatinee ? $this->roundPrice($kids * 0.85) : null;

                $rowPrices[] = [
                    'show_id' => $show['id'],
                    'row_label' => $rowLabel,
                    'tier_name' => $tier,
                    'benefits' => $benefits,
                    'price' => $price,
                    'sale_price' => $sale,
                    'kids_price' => $kids,
                    'kids_sale_price' => $kidsSale,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (! isset($perCategory[$category]) || $price < $perCategory[$category]['price']) {
                    $perCategory[$category] = ['price' => $price, 'sale_price' => $sale, 'kids_price' => $kids, 'kids_sale_price' => $kidsSale];
                }
            }

            foreach ($perCategory as $category => $price) {
                $categoryPrices[] = ['show_id' => $show['id'], 'seat_category_id' => $categories[$category], ...$price, 'created_at' => now(), 'updated_at' => now()];
            }
        }

        foreach (array_chunk($rowPrices, 500) as $chunk) {
            DB::table('show_seat_row_prices')->insert($chunk);
        }

        foreach (array_chunk($categoryPrices, 500) as $chunk) {
            DB::table('show_seat_prices')->insert($chunk);
        }
    }

    private function roundPrice(float $price): int
    {
        return (int) (round($price / 50) * 50);
    }

    /**
     * @param  array<string, array{id: int, reviews: list<array{0: int, 1: string}>}>  $movies
     */
    private function seedReviews(array $movies): void
    {
        $reviewers = $this->customerIds;
        $offset = 0;

        foreach ($movies as $movie) {
            foreach ($movie['reviews'] as $index => [$rating, $text]) {
                Review::create([
                    'user_id' => $reviewers[($offset + $index) % count($reviewers)],
                    'movie_id' => $movie['id'],
                    'rating' => $rating,
                    'review_text' => $text,
                    'is_approved' => true,
                    'is_flagged' => false,
                    'approved_by' => $this->adminId,
                    'approved_at' => now()->subDays(mt_rand(1, 14)),
                    'created_at' => now()->subDays(mt_rand(1, 20)),
                ]);
            }

            $offset += 5;
            Movie::find($movie['id'])->refreshRating();
        }
    }

    /**
     * Creates real bookings, so seat maps show genuine occupancy that flows
     * through the same tables, views and constraints as a live checkout.
     *
     * @param  list<array{id: int, screen: array, date: string, time: string, status: string}>  $shows
     */
    private function seedBookings(array $shows): void
    {
        $seatsByScreen = DB::table('seats')
            ->orderBy('seat_number')
            ->get(['id', 'screen_id', 'row_label', 'seat_number', 'seat_category_id'])
            ->groupBy('screen_id');
        $rowPrices = DB::table('show_seat_row_prices')->get()->groupBy('show_id');
        $horizon = now()->startOfDay()->addDays(3)->toDateString();
        $tomorrow = now()->startOfDay()->addDay()->toDateString();

        // The demo account always has two upcoming bookings to explore.
        $demoShows = collect($shows)
            ->filter(fn (array $show) => $show['date'] === $tomorrow && $show['time'] === '20:00:00')
            ->take(2)
            ->pluck('id')
            ->all();

        foreach ($shows as $show) {
            if ($show['date'] >= $horizon) {
                continue;
            }

            $rows = $seatsByScreen[$show['screen']['id']]->groupBy('row_label')->map(fn ($seats) => $seats->values());
            $prices = $rowPrices[$show['id']]->keyBy('row_label');
            $target = (int) round($show['screen']['seats'] * mt_rand(8, 55) / 100);
            $taken = 0;
            $needsDemo = in_array($show['id'], $demoShows, true);

            while ($taken < $target) {
                $party = mt_rand(1, 4);
                $candidates = $rows->filter(fn ($seats) => $seats->count() >= $party);

                if ($candidates->isEmpty()) {
                    break;
                }

                // Parties sit together: take a run of neighbouring free seats in one row.
                $rowLabel = $candidates->keys()->random();
                $start = mt_rand(0, $rows[$rowLabel]->count() - $party);
                $seats = $rows[$rowLabel]->splice($start, $party);

                $userId = $needsDemo ? $this->demoUserId : $this->customerIds[array_rand($this->customerIds)];
                $needsDemo = false;

                $this->createBooking($show, $userId, $seats, $prices);
                $taken += $party;
            }

            DB::table('shows')->where('id', $show['id'])->update(['booked_seats' => $taken]);
        }
    }

    private function createBooking(array $show, int $userId, $seats, $prices): void
    {
        $number = 'BM-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        $bookedAt = Carbon::parse($show['date'].' '.$show['time'])->subHours(mt_rand(3, 96));
        $bookedAt = $bookedAt->isFuture() ? now()->subMinutes(mt_rand(5, 600)) : $bookedAt;
        $completed = $show['status'] === 'completed';
        $paid = $completed || mt_rand(1, 3) === 1;

        $lines = $seats->map(function ($seat) use ($prices, $number) {
            $row = $prices[$seat->row_label];
            $isKid = $row->kids_price !== null && mt_rand(1, 6) === 1;
            $price = $isKid ? ($row->kids_sale_price ?? $row->kids_price) : ($row->sale_price ?? $row->price);

            return [
                'seat_id' => $seat->id,
                'seat_category_id' => $seat->seat_category_id,
                'ticket_type' => $isKid ? 'kid' : 'adult',
                'price_paid' => $price,
                'ticket_number' => $number.'-'.$seat->row_label.$seat->seat_number,
            ];
        });

        $subtotal = (float) $lines->sum('price_paid');
        $user = DB::table('users')->where('id', $userId)->first(['name', 'email', 'phone', 'address']);

        $bookingId = DB::table('bookings')->insertGetId([
            'booking_number' => $number,
            'user_id' => $userId,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'customer_address' => $user->address ?: 'Provided at the counter',
            'show_id' => $show['id'],
            'seat_count' => $lines->count(),
            'adult_count' => $lines->where('ticket_type', 'adult')->count(),
            'kids_count' => $lines->where('ticket_type', 'kid')->count(),
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'payment_method' => 'cod',
            'payment_status' => $paid ? 'paid' : 'pending',
            'booking_status' => $completed ? 'completed' : 'confirmed',
            'booked_at' => $bookedAt,
            'updated_at' => $bookedAt,
        ]);

        DB::table('booking_seats')->insert($lines->map(fn (array $line) => [
            ...$line,
            'booking_id' => $bookingId,
            'show_id' => $show['id'],
            'seat_lock' => 1,
            'created_at' => $bookedAt,
        ])->all());

        DB::table('payments')->insert([
            'booking_id' => $bookingId,
            'payment_method' => 'cod',
            'amount' => $subtotal,
            'status' => $paid ? 'paid' : 'pending',
            'transaction_reference' => $paid ? 'CTR-'.strtoupper(Str::random(10)) : null,
            'notes' => 'Pay at the cinema box office.',
            'paid_at' => $paid ? $bookedAt->copy()->addHours(2) : null,
            'created_at' => $bookedAt,
            'updated_at' => $bookedAt,
        ]);

        $events = [['event' => 'confirmed', 'from_status' => null, 'to_status' => 'confirmed', 'note' => 'Booking confirmed online.', 'actor_type' => 'user', 'created_at' => $bookedAt]];

        if ($paid) {
            $events[] = ['event' => 'payment_received', 'from_status' => 'pending', 'to_status' => 'paid', 'note' => 'Paid at the box office.', 'actor_type' => 'admin', 'created_at' => $bookedAt->copy()->addHours(2)];
        }

        if ($completed) {
            $events[] = ['event' => 'checked_in', 'from_status' => 'confirmed', 'to_status' => 'completed', 'note' => 'Tickets scanned at the screen door.', 'actor_type' => 'system', 'created_at' => Carbon::parse($show['date'].' '.$show['time'])->subMinutes(10)];
        }

        DB::table('booking_events')->insert(array_map(fn (array $event) => [...$event, 'booking_id' => $bookingId, 'actor_id' => null], $events));
    }

    private function seedCoupons(): void
    {
        $coupons = [
            ['WELCOME15', 'New member offer: 15% off your first booking, up to PKR 600.', 'percentage', 15, 600, 1500, 1000, 1, -2, 60],
            ['MATINEE200', 'PKR 200 off any booking of PKR 2,500 or more.', 'fixed', 200, null, 2500, 500, 3, -5, 30],
            ['FAMILY10', '10% off bookings of PKR 4,000 or more, up to PKR 1,000.', 'percentage', 10, 1000, 4000, null, 5, -10, 90],
            ['SUMMERFEST', 'Summer festival offer (expired).', 'percentage', 20, 500, 1000, 300, 1, -120, -60],
        ];

        foreach ($coupons as [$code, $description, $type, $value, $cap, $minimum, $maxUses, $perUser, $from, $until]) {
            DB::table('coupons')->insert([
                'code' => $code,
                'description' => $description,
                'discount_type' => $type,
                'discount_value' => $value,
                'max_discount_amount' => $cap,
                'min_order_amount' => $minimum,
                'max_uses' => $maxUses,
                'used_count' => 0,
                'max_uses_per_user' => $perUser,
                'valid_from' => now()->addDays($from),
                'valid_until' => now()->addDays($until),
                'is_active' => true,
                'created_by' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedSiteSettings(): void
    {
        $settings = [
            'site_name' => 'BookMyMovie',
            'default_meta_title' => 'BookMyMovie | Cinema Tickets, Showtimes & Seats',
            'default_meta_description' => 'Book cinema tickets online across Pakistan. Live seat maps, honest row pricing, IMAX and Dolby showtimes, and no booking fees.',
            'canonical_base_url' => config('bookmymovie.canonical_url'),
            'site_tagline' => 'Every seat, every showtime, honestly priced.',
            'footer_description' => 'An independent, open-source cinema booking platform. Live seat maps, row-by-row pricing and tickets that just work at the door.',
            'copyright_note' => 'By Syed Ahmer Shah · MIT licence',
            'support_email' => 'support@ahmershah.dev',
            'support_phone' => '+92 370 4831994',
            'service_area' => 'Karachi, Lahore, Islamabad and Rawalpindi',
            'response_sla' => 'Within one working day',
            'contact_heading' => 'We read every message.',
            'contact_intro' => 'Questions about a booking, a partnership enquiry or a bug report. Include your booking number and city and we will get back to you within one working day.',
            'catalog_heading' => 'In cinemas',
            'catalog_intro' => 'Every film playing across our partner cinemas this week, plus what is coming next. Filter by genre, language, rating or status.',
            'home_ticker_messages' => 'No booking fees, ever|Weekday matinées from PKR 950|IMAX with Laser now in Karachi and Lahore|Kids’ tickets on most rows|Pay at the counter, cancel free until 2 hours before',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'string', 'is_public' => true]);
        }
    }

    private function seedContentPages(): void
    {
        foreach (require __DIR__.'/data/pages.php' as $page) {
            ContentPage::updateOrCreate(['slug' => $page['slug']], [...$page, 'created_by' => $this->adminId, 'is_active' => true]);
        }
    }

    private function seedFaqs(): void
    {
        foreach (require __DIR__.'/data/faqs.php' as $index => [$category, $question, $answer]) {
            DB::table('faqs')->insert([
                'category' => $category,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_by' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedInboxAndNotifications(): void
    {
        DB::table('contact_messages')->insert([
            ['name' => 'Rehan Aslam', 'email' => 'rehan.aslam@example.com', 'subject' => 'Group booking', 'message' => 'Hi, we are a school group of 28 students. Can we reserve a full row block for a Saturday matinée of Whistle of the Night Heron?', 'user_id' => null, 'is_read' => false, 'is_replied' => false, 'created_at' => now()->subHours(5)],
            ['name' => 'Maryam Qureshi', 'email' => 'maryam.qureshi@example.com', 'subject' => 'Accessibility', 'message' => 'Does the Lumière Grand IMAX screen have wheelchair spaces near the middle rows? My father uses a wheelchair.', 'user_id' => $this->customerIds[0], 'is_read' => true, 'is_replied' => false, 'created_at' => now()->subDay()],
        ]);

        DB::table('notifications')->insert([
            ['user_id' => $this->demoUserId, 'type' => 'offer', 'title' => 'Welcome to BookMyMovie', 'message' => 'Use WELCOME15 for 15% off your first booking.', 'is_read' => false, 'created_at' => now()->subDays(2)],
            ['user_id' => $this->demoUserId, 'type' => 'release', 'title' => 'Iron Orchard opens soon', 'message' => 'A film on your radar opens in cinemas next week.', 'is_read' => false, 'created_at' => now()->subHours(8)],
        ]);
    }
}
