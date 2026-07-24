<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\ContentPage;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\SeatCategory;
use App\Models\Show;
use App\Models\ShowSeatPrice;
use App\Models\SiteSetting;
use App\Models\Theater;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@bookmymovie.test';

    private const ADMIN_PASSWORD = 'AdminReset@2026!';

    public function run(): void
    {
        $this->clearDemoData();

        $admin = $this->seedAdmins();
        $reviewUser = $this->seedUsers();
        $categories = $this->seedCategories();
        $this->seedCinema($admin, $categories, $reviewUser);
        $this->seedSiteSettings();
        $this->seedContentPages($admin);
        $this->seedSupportContent($admin);
    }

    private function clearDemoData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'contact_messages',
            'faqs',
            'content_pages',
            'site_settings',
            'admin_activity_logs',
            'admin_notifications',
            'notifications',
            'wishlists',
            'reviews',
            'coupon_usages',
            'payments',
            'booking_seats',
            'bookings',
            'cart_items',
            'carts',
            'coupons',
            'show_seat_prices',
            'shows',
            'seats',
            'seat_categories',
            'screens',
            'theaters',
            'movie_genres',
            'movies',
            'genres',
            'users',
            'admins',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function seedAdmins(): Admin
    {
        $superAdmin = Admin::updateOrCreate(['email' => self::ADMIN_EMAIL], [
            'name' => 'BookMyMovie Admin',
            'password' => Hash::make(self::ADMIN_PASSWORD),
            'role' => 'superadmin',
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        Admin::updateOrCreate(['email' => 'content@bookmymovie.test'], [
            'name' => 'Content Admin',
            'password' => Hash::make(self::ADMIN_PASSWORD),
            'role' => 'admin',
            'is_active' => true,
        ]);

        return $superAdmin;
    }

    private function seedUsers(): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Password@123'),
            'phone' => '03001234567',
            'date_of_birth' => '1998-07-24',
            'gender' => 'male',
        ]);
    }

    /**
     * @return array<string, Genre>
     */
    private function seedCategories(): array
    {
        return collect([
            'broke-treat-friend' => 'The "Always Broke / Demanding Treat" Friend',
            'single-romantic-friend' => 'The "Forever Single / Desperate Romantic" Friend',
            'late-ghosting-friend' => 'The "Always Late / Ghosting" Friend',
            'drama-overthinker-friend' => 'The "Drama Queen / Overthinker" Friend',
            'lazy-sleepy-friend' => 'The "Lazy / Always Sleepy" Friend',
        ])->mapWithKeys(fn (string $name, string $slug) => [
            $slug => Genre::create(['name' => $name, 'slug' => $slug]),
        ])->all();
    }

    /**
     * @param array<string, Genre> $categories
     */
    private function seedCinema(Admin $admin, array $categories, User $reviewUser): void
    {
        SeatCategory::insert([
            ['name' => 'Gold', 'description' => 'Standard seats with a clear screen view'],
            ['name' => 'Platinum', 'description' => 'Premium seats with extra legroom'],
            ['name' => 'Box', 'description' => 'Private box seating for groups'],
        ]);

        $theaters = [
            Theater::create([
                'name' => 'Cineplex Gold',
                'address' => 'Shop 12, Dolmen Mall, Block-4 Clifton',
                'city' => 'Karachi',
                'state' => 'Sindh',
                'pincode' => '75600',
                'phone' => '021-35861010',
                'email' => 'info@cineplexgold.pk',
                'created_by' => $admin->id,
            ]),
            Theater::create([
                'name' => 'Star Cinemas',
                'address' => '3-KM Main Canal Bank Road, Emporium Mall',
                'city' => 'Lahore',
                'state' => 'Punjab',
                'pincode' => '54000',
                'phone' => '042-35880110',
                'email' => 'bookings@starcinemas.pk',
                'created_by' => $admin->id,
            ]),
        ];

        $screens = collect([
            [$theaters[0]->id, 'Audi 1'],
            [$theaters[0]->id, 'Audi 2'],
            [$theaters[1]->id, 'Screen A'],
            [$theaters[1]->id, 'Screen B'],
        ])->map(fn (array $screen) => Screen::create([
            'theater_id' => $screen[0],
            'screen_name' => $screen[1],
            'total_seats' => 60,
            'is_active' => true,
        ]))->values();

        $screens->each(fn (Screen $screen) => $this->seedSeats($screen));

        collect($this->movies())->each(function (array $movieData, int $index) use ($admin, $categories, $screens, $reviewUser) {
            $movie = Movie::create([
                'title' => $movieData['title'],
                'slug' => Str::slug($movieData['title']),
                'description' => $movieData['description'],
                'language' => 'Urdu / Roman Urdu',
                'duration_minutes' => $movieData['duration'],
                'certificate_rating' => $movieData['certificate'],
                'release_date' => now()->subDays(20 - $index)->toDateString(),
                'status' => 'now_showing',
                'poster_image' => null,
                'banner_image' => null,
                'trailer_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'base_price' => 2500,
                'sale_price' => 1999,
                'kids_discount_eligible' => false,
                'average_rating' => $movieData['rating'],
                'total_reviews' => 1,
                'created_by' => $admin->id,
            ]);

            $movie->genres()->attach($categories[$movieData['category']]->id);
            Review::create([
                'user_id' => $reviewUser->id,
                'movie_id' => $movie->id,
                'rating' => (int) round($movieData['rating']),
                'review_text' => $movieData['review'],
                'is_approved' => true,
                'is_flagged' => false,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            $screen = $screens[$index % $screens->count()];
            $show = Show::create([
                'movie_id' => $movie->id,
                'screen_id' => $screen->id,
                'show_date' => now()->addDays(intdiv($index, $screens->count()))->toDateString(),
                'show_time' => ['10:00:00', '13:30:00', '17:00:00', '20:30:00'][$index % 4],
                'status' => 'scheduled',
                'total_seats' => 60,
                'booked_seats' => 0,
                'created_by' => $admin->id,
            ]);

            $this->seedShowPrices($show);
        });
    }

    private function seedSeats(Screen $screen): void
    {
        $categoryByRow = [
            'A' => 1,
            'B' => 1,
            'C' => 1,
            'D' => 2,
            'E' => 2,
            'F' => 3,
        ];

        foreach ($categoryByRow as $row => $categoryId) {
            for ($seatNumber = 1; $seatNumber <= 10; $seatNumber++) {
                Seat::create([
                    'screen_id' => $screen->id,
                    'seat_category_id' => $categoryId,
                    'row_label' => $row,
                    'seat_number' => $seatNumber,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function seedShowPrices(Show $show): void
    {
        ShowSeatPrice::insert([
            ['show_id' => $show->id, 'seat_category_id' => 1, 'price' => 2500, 'sale_price' => 1999, 'kids_price' => 1800, 'kids_sale_price' => 1499],
            ['show_id' => $show->id, 'seat_category_id' => 2, 'price' => 2800, 'sale_price' => 2249, 'kids_price' => 2100, 'kids_sale_price' => 1699],
            ['show_id' => $show->id, 'seat_category_id' => 3, 'price' => 3200, 'sale_price' => 2499, 'kids_price' => null, 'kids_sale_price' => null],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function movies(): array
    {
        return [
            ['category' => 'broke-treat-friend', 'title' => 'Treat Kab Dera Hai?: The Never-Ending Struggle', 'description' => 'A broke friend dodges every bill while demanding celebration treats from everyone else.', 'duration' => 104, 'certificate' => 'UA', 'rating' => 4.4, 'review' => 'Painfully accurate, especially the bill-splitting scene.'],
            ['category' => 'broke-treat-friend', 'title' => 'EasyPaisa Mein Rs. 15: Based on a True Story', 'description' => 'One suspicious wallet balance becomes the emotional center of a whole friend group.', 'duration' => 96, 'certificate' => 'U', 'rating' => 4.1, 'review' => 'The Rs. 15 reveal got the biggest laugh in the hall.'],
            ['category' => 'broke-treat-friend', 'title' => 'Paisa Kahan Gaya?: An Unsolved Mystery', 'description' => 'A group investigates how pocket money vanishes before the bill arrives.', 'duration' => 111, 'certificate' => 'UA', 'rating' => 4.3, 'review' => 'A mystery every student understands too well.'],
            ['category' => 'broke-treat-friend', 'title' => 'Bhai Account Blank Hai: The Saga Continues', 'description' => 'The classic excuse returns with bigger plans, smaller balances, and louder friends.', 'duration' => 118, 'certificate' => 'U', 'rating' => 4.0, 'review' => 'Solid sequel energy with very real account balance trauma.'],
            ['category' => 'single-romantic-friend', 'title' => 'Chalo Shadi Karte Hain: Part 2: Ek Aur Kat Gaya', 'description' => 'A serial romantic restarts the wedding countdown after another almost-love story collapses.', 'duration' => 127, 'certificate' => 'UA', 'rating' => 4.5, 'review' => 'The romance panic is dramatic but weirdly wholesome.'],
            ['category' => 'single-romantic-friend', 'title' => 'Bhabhi Dhoond Do Koi: A Tale of Hope and Rejection', 'description' => 'A hopeful single friend outsources romance to the most unqualified committee possible.', 'duration' => 115, 'certificate' => 'U', 'rating' => 4.2, 'review' => 'The friend group matchmaking committee was chaos.'],
            ['category' => 'single-romantic-friend', 'title' => 'Mera Dil Hai Ya Sabzi Mandi?: Sab Aate Hain, Sab Jaate Hain', 'description' => 'Everyone visits, nobody stays, and one dramatic heart keeps reopening for business.', 'duration' => 122, 'certificate' => 'UA', 'rating' => 4.6, 'review' => 'Best title, best monologue, best heartbreak jokes.'],
            ['category' => 'single-romantic-friend', 'title' => 'Rishta Confirm Karo: The Sequel Nobody Asked For', 'description' => 'Every casual conversation becomes a marriage proposal review meeting.', 'duration' => 109, 'certificate' => 'U', 'rating' => 4.1, 'review' => 'The family pressure scenes are too familiar.'],
            ['category' => 'late-ghosting-friend', 'title' => 'Main Bas 5 Minute Door Hoon: The Biggest Lie Ever Told', 'description' => 'A friend claims to be nearby while the entire city waits in disbelief.', 'duration' => 101, 'certificate' => 'U', 'rating' => 4.7, 'review' => 'Everyone has heard this lie and everyone laughed.'],
            ['category' => 'late-ghosting-friend', 'title' => 'Kahan Ho Tum?: A Thriller About Getting Ghosted', 'description' => 'Seen receipts, missing replies, and one chat window turn into a suspense case.', 'duration' => 108, 'certificate' => 'UA', 'rating' => 4.4, 'review' => 'The seen-zone suspense was better than expected.'],
            ['category' => 'late-ghosting-friend', 'title' => 'Agli Sadi Mein Milte Hain: Directed by Delayed Expectations', 'description' => 'Plans are made, postponed, and emotionally rescheduled into the next century.', 'duration' => 113, 'certificate' => 'U', 'rating' => 4.0, 'review' => 'A slow-burn comedy about waiting forever.'],
            ['category' => 'late-ghosting-friend', 'title' => 'Online Hai Par Reply Nahi Kar Raha: A Psychological Horror', 'description' => 'The green dot is active, the silence is louder, and nobody is coping well.', 'duration' => 117, 'certificate' => 'A', 'rating' => 4.8, 'review' => 'Genuinely hilarious horror for the WhatsApp era.'],
            ['category' => 'drama-overthinker-friend', 'title' => 'Mera Gumshuda Bacha: Return of the Lost Braincells', 'description' => 'One misplaced thought launches a dramatic search party through bad decisions.', 'duration' => 103, 'certificate' => 'UA', 'rating' => 4.2, 'review' => 'The overthinking spiral was painfully detailed.'],
            ['category' => 'drama-overthinker-friend', 'title' => 'Rona Dhona Ltd.: 100% Pure Drama, 0% Logic', 'description' => 'A corporate empire of tears expands faster than common sense can respond.', 'duration' => 99, 'certificate' => 'U', 'rating' => 4.3, 'review' => 'Pure drama with exactly the right amount of nonsense.'],
            ['category' => 'drama-overthinker-friend', 'title' => 'Mujhe Pehle Hi Pata Tha: The Expert in Hindsight', 'description' => 'After every disaster, one friend explains how they predicted it all along.', 'duration' => 107, 'certificate' => 'U', 'rating' => 4.1, 'review' => 'The hindsight expert deserves a spin-off.'],
            ['category' => 'drama-overthinker-friend', 'title' => 'Chhoti Si Baat, Bada Bawaal: An Epic Disaster', 'description' => 'A tiny misunderstanding mutates into a full-scale group chat emergency.', 'duration' => 119, 'certificate' => 'UA', 'rating' => 4.6, 'review' => 'Peak group-chat disaster cinema.'],
            ['category' => 'lazy-sleepy-friend', 'title' => 'Kal Se Gym Jaunga: A Mythical Fantasy', 'description' => 'A legendary fitness plan keeps moving to tomorrow with heroic consistency.', 'duration' => 102, 'certificate' => 'U', 'rating' => 4.4, 'review' => 'The tomorrow joke never got old.'],
            ['category' => 'lazy-sleepy-friend', 'title' => 'Mujhe Sone Do: The 14-Hour Sleep Spree', 'description' => 'One sleepy friend protects nap time from alarms, plans, and basic responsibility.', 'duration' => 95, 'certificate' => 'U', 'rating' => 4.2, 'review' => 'Comfort movie for anyone who loves sleeping in.'],
            ['category' => 'lazy-sleepy-friend', 'title' => 'Sofa Se Bed Tak: An Action-Packed Journey of 3 Steps', 'description' => 'The shortest journey becomes the most exhausting mission of the day.', 'duration' => 90, 'certificate' => 'U', 'rating' => 4.0, 'review' => 'Three steps, huge stakes, excellent laziness.'],
            ['category' => 'lazy-sleepy-friend', 'title' => 'Utho, Chai Piyo, Phir So Jao: The Eternal Cycle', 'description' => 'Wake up, drink tea, return to sleep, and repeat until the world gives up.', 'duration' => 106, 'certificate' => 'U', 'rating' => 4.5, 'review' => 'The chai-to-nap cycle is cinema truth.'],
        ];
    }

    private function seedSiteSettings(): void
    {
        collect([
            'site_name' => 'BookMyMovie',
            'site_tagline' => 'Database-powered cinema booking with live shows, sale pricing, reviews, and seat selection.',
            'footer_description' => 'BookMyMovie is your all-in-one digital cinema companion for showtimes, sale prices, reviews, wishlists, and secure seat booking.',
            'copyright_note' => 'Created by Syed Ahmer Shah',
            'support_email' => 'support@bookmymovie.test',
            'support_phone' => '021-111-266-566',
            'service_area' => 'Pakistan',
            'response_sla' => 'Under 24 hours',
            'contact_heading' => 'Talk to BookMyMovie support.',
            'contact_intro' => 'Send booking inquiries, cinema partnership requests, or account assistance. Provide your city, booking number, and showtime so support can resolve your query efficiently.',
            'catalog_heading' => 'Explore Movies',
            'catalog_intro' => 'Filter through the database catalog by genre, language, age certification, and availability status.',
            'home_ticker_messages' => 'Sale is live: every movie starts around PKR 2,500 with database sale prices.|All 20 friend-category movies are now showing in five database categories.|Reviews, ratings, showtimes, and prices are loaded from the database.|Secure forms use Laravel CSRF, validation, throttling, and CSP nonce headers.',
        ])->each(fn (string $value, string $key) => SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => 'string', 'is_public' => true]
        ));
    }

    private function seedContentPages(Admin $admin): void
    {
        foreach ($this->pages() as $page) {
            ContentPage::updateOrCreate(
                ['slug' => $page['slug']],
                [...$page, 'created_by' => $admin->id, 'is_active' => true]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'about',
                'title' => 'About BookMyMovie',
                'meta_title' => 'About',
                'hero_label' => 'Company',
                'excerpt' => 'BookMyMovie stores site content, movies, categories, reviews, showtimes, seat prices, FAQs, and booking records in Laravel database tables.',
                'body' => 'BookMyMovie is a Laravel cinema booking project designed around database-first content management. The public website reads seeded content and operational data from migrations, seeders, Eloquent models, controllers, and database views.',
                'sections' => [
                    ['title' => 'Database First', 'items' => ['Site name and footer copy come from site_settings.', 'Policies and about content come from content_pages.', 'Movies, categories, reviews, shows, and prices come from cinema tables.']],
                    ['title' => 'Booking Flow', 'items' => ['Users browse movies by category.', 'Seats are checked through the availability view.', 'Bookings are stored with tickets and COD payment records.']],
                ],
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of Service',
                'meta_title' => 'Terms of Service',
                'hero_label' => 'Legal',
                'excerpt' => 'These terms describe responsible use of BookMyMovie accounts, movies, carts, seat selection, and booking records.',
                'body' => 'Use accurate account details, keep credentials private, and only reserve seats you intend to book. Seat availability can change when another user books or reserves the same seat first.',
                'sections' => [
                    ['title' => 'Accounts', 'items' => ['Use a valid email address.', 'Do not share admin or user credentials.', 'Blocked accounts cannot login or book.']],
                    ['title' => 'Bookings', 'items' => ['Cart seats expire after the configured hold window.', 'Bookings are confirmed only after checkout completes.', 'COD payments are collected at the cinema counter.']],
                ],
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy',
                'hero_label' => 'Privacy',
                'excerpt' => 'This policy explains how BookMyMovie handles account, booking, contact, wishlist, review, and session data.',
                'body' => 'BookMyMovie stores only the information needed to run cinema booking workflows, authenticate accounts, protect forms, and display user-owned booking history.',
                'sections' => [
                    ['title' => 'Stored Data', 'items' => ['Account name, email, phone, and hashed passwords.', 'Bookings, selected seats, payments, carts, wishlists, reviews, and contact messages.', 'Session, IP, and user-agent details needed for security logs and sessions.']],
                    ['title' => 'Protection', 'items' => ['Passwords are hashed by Laravel casts.', 'Forms use CSRF tokens and validation.', 'Public pages are protected by CSP, frame, referrer, and content-type headers.']],
                ],
            ],
            [
                'slug' => 'refund',
                'title' => 'Refund & Cancellation Policy',
                'meta_title' => 'Refund Policy',
                'hero_label' => 'Support',
                'excerpt' => 'BookMyMovie supports clear cancellation guidance for COD bookings, expired carts, unavailable seats, and cancelled shows.',
                'body' => 'Because this project uses cash on delivery at the cinema counter, most unpaid bookings can be cancelled operationally before the show starts. Paid or counter-confirmed cases require admin review.',
                'sections' => [
                    ['title' => 'Eligible Cases', 'items' => ['Cancelled shows.', 'Duplicate booking records.', 'Seats made unavailable by a cinema operation issue.']],
                    ['title' => 'Not Eligible', 'items' => ['Expired carts that were never checked out.', 'No-shows after the show begins.', 'Incorrect details entered by the customer without contacting support.']],
                ],
            ],
            [
                'slug' => 'eticket-info',
                'title' => 'E-Ticket Information',
                'meta_title' => 'E-Ticket Info',
                'hero_label' => 'Tickets',
                'excerpt' => 'Booking numbers, ticket numbers, show details, seat labels, and COD payment status are stored in the database.',
                'body' => 'After checkout, BookMyMovie creates a booking number and ticket number for each selected seat. Users can view booking details, tracking, and seat information from their account.',
                'sections' => [
                    ['title' => 'What You Receive', 'items' => ['Booking number.', 'Movie, theater, screen, date, and time.', 'Seat labels and ticket numbers.']],
                    ['title' => 'At The Counter', 'items' => ['Show your booking number.', 'Pay the COD amount if still pending.', 'Collect the physical ticket where required by the cinema.']],
                ],
            ],
        ];
    }

    private function seedSupportContent(Admin $admin): void
    {
        Coupon::create([
            'code' => 'FRIENDS20',
            'description' => 'Opening offer for friend-category movies',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_discount_amount' => 300,
            'min_order_amount' => 1000,
            'max_uses' => 200,
            'max_uses_per_user' => 1,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        collect([
            ['Booking', 'Can I book seats for these movies?', 'Yes. Every seeded movie has an active scheduled show and seat pricing.'],
            ['Account', 'Which admin login should I use?', 'Use admin@bookmymovie.test with the refreshed project password.'],
            ['Movies', 'Are the five friend types categories?', 'Yes. They are stored in the genres table and used by the movie filters.'],
            ['Security', 'Do forms use CSRF protection?', 'Yes. POST forms include Laravel CSRF tokens, server-side validation, throttling on sensitive routes, and CSP nonce headers.'],
        ])->each(fn (array $faq, int $index) => Faq::create([
            'category' => $faq[0],
            'question' => $faq[1],
            'answer' => $faq[2],
            'sort_order' => $index + 1,
            'is_active' => true,
            'created_by' => $admin->id,
        ]));
    }
}
