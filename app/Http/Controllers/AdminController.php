<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\ContentPage;
use App\Models\Coupon;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Notification;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AuditLog;
use App\Support\MovieImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use RuntimeException;

class AdminController extends Controller
{
    /**
     * The command centre: today's money and seats, what needs attention,
     * tonight's schedule, trends and a guided setup checklist. Figures are
     * cached for a minute so a busy box office can keep it open all day.
     */
    public function dashboard(Request $request): Response|RedirectResponse
    {
        $admin = $this->admin($request);

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $data = \Illuminate\Support\Facades\Cache::remember('admin.overview', now()->addMinute(), fn () => $this->overviewData());

        return $this->page('Admin/Dashboard', [
            ...$data,
            'admin' => ['name' => $admin->name, 'role' => $admin->roleLabel(), 'twoFactor' => $admin->hasTwoFactor()],
            'setup' => [
                ['key' => 'films', 'label' => 'Add your films', 'detail' => 'Title, artwork, trailer and certificate. Coming-soon films can be added early.', 'done' => Movie::query()->exists(), 'href' => route('admin.movies')],
                ['key' => 'shows', 'label' => 'Schedule shows and row prices', 'detail' => 'Each show has its own price per row, adult and child.', 'done' => DB::table('shows')->where('show_date', '>=', now()->toDateString())->exists(), 'href' => route('admin.movies')],
                ['key' => 'coupon', 'label' => 'Create a launch coupon', 'detail' => 'Set a hard use limit so a rush can never overspend it.', 'done' => Coupon::query()->where('is_active', true)->exists(), 'href' => route('admin.commerce')],
                ['key' => 'snacks', 'label' => 'Set snack stock', 'detail' => 'Pre-orders stop automatically when stock runs out.', 'done' => DB::table('concessions')->whereNotNull('stock')->exists(), 'href' => route('admin.commerce')],
                ['key' => 'staff', 'label' => 'Invite your box office team', 'detail' => 'Box office staff can check tickets in but cannot change prices.', 'done' => Admin::query()->where('is_active', true)->count() > 1, 'href' => route('admin.staff')],
                ['key' => '2fa', 'label' => 'Turn on two-step sign-in', 'detail' => 'An authenticator code on top of your password.', 'done' => $admin->hasTwoFactor(), 'href' => route('admin.security')],
            ],
        ], ['title' => 'Overview | Admin', 'description' => 'BookMyMovie box office overview.', 'robots' => 'noindex, nofollow']);
    }

    /** @return array<string, mixed> */
    private function overviewData(): array
    {
        $today = now()->toDateString();
        $paid = fn () => Booking::query()->where('booking_status', '<>', 'cancelled');
        $money = 'COALESCE(SUM(total_amount + gift_card_amount), 0)';

        // Last 14 days of revenue and tickets, gaps filled with zeros.
        $from = now()->subDays(13)->startOfDay();
        $daily = $paid()->where('booked_at', '>=', $from)->selectRaw("DATE(booked_at) AS day, {$money} AS revenue, COALESCE(SUM(seat_count), 0) AS tickets")->groupBy('day')->get()->keyBy('day');
        $series = collect(range(0, 13))->map(function (int $offset) use ($from, $daily) {
            $day = $from->copy()->addDays($offset)->toDateString();

            return ['day' => $day, 'label' => Carbon::parse($day)->format('D j'), 'revenue' => round((float) ($daily[$day]->revenue ?? 0), 2), 'tickets' => (int) ($daily[$day]->tickets ?? 0)];
        })->values();

        $members = User::query()->where('created_at', '>=', now()->subDays(13)->startOfDay())->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')->groupBy('day')->pluck('total', 'day');
        $memberSeries = $series->map(fn ($day) => (int) ($members[$day['day']] ?? 0))->values();

        // Tonight: every show today, on a timeline per screen.
        $tonight = DB::table('v_show_details')->where('show_date', $today)->where('show_status', '<>', 'cancelled')
            ->orderBy('theater_name')->orderBy('screen_name')->orderBy('show_time')
            ->get(['show_id', 'show_time', 'movie_title', 'movie_slug', 'duration_minutes', 'theater_name', 'screen_name', 'screen_format', 'total_seats', 'booked_seats'])
            ->map(fn ($show) => [
                'id' => (int) $show->show_id,
                'film' => $show->movie_title,
                'slug' => $show->movie_slug,
                'screen' => $show->theater_name.' · '.$show->screen_name,
                'start' => substr((string) $show->show_time, 0, 5),
                'minutes' => (int) ($show->duration_minutes ?: 120),
                'sold' => (int) $show->booked_seats,
                'seats' => (int) $show->total_seats,
            ])->values();

        // Seats filled by weekday and start hour, last 30 days and next 7.
        $heat = DB::table('shows')->where('status', '<>', 'cancelled')->where('total_seats', '>', 0)
            ->whereBetween('show_date', [now()->subDays(30)->toDateString(), now()->addDays(7)->toDateString()])
            ->selectRaw('WEEKDAY(show_date) AS weekday, HOUR(show_time) AS hour, SUM(booked_seats) AS sold, SUM(total_seats) AS seats')
            ->groupBy('weekday', 'hour')->get()
            ->map(fn ($row) => ['weekday' => (int) $row->weekday, 'hour' => (int) $row->hour, 'rate' => $row->seats ? round($row->sold / $row->seats * 100) : 0, 'sold' => (int) $row->sold, 'seats' => (int) $row->seats])->values();

        $top = DB::table('bookings')->join('shows', 'shows.id', '=', 'bookings.show_id')->join('movies', 'movies.id', '=', 'shows.movie_id')
            ->where('bookings.booking_status', '<>', 'cancelled')->where('bookings.booked_at', '>=', now()->subDays(30))
            ->selectRaw('movies.id, movies.title, movies.slug, movies.poster_image, SUM(bookings.seat_count) AS tickets, SUM(bookings.total_amount + bookings.gift_card_amount) AS revenue')
            ->groupBy('movies.id', 'movies.title', 'movies.slug', 'movies.poster_image')->orderByDesc('tickets')->limit(5)->get()
            ->map(fn ($row) => ['title' => $row->title, 'slug' => $row->slug, 'poster' => $row->poster_image ? (str_starts_with($row->poster_image, 'http') ? $row->poster_image : asset(str_starts_with($row->poster_image, 'images/') ? $row->poster_image : 'storage/'.$row->poster_image)) : null, 'tickets' => (int) $row->tickets, 'revenue' => round((float) $row->revenue, 2)])->values();

        $methods = $paid()->where('booked_at', '>=', now()->subDays(30))->selectRaw('payment_method, COUNT(*) AS total')->groupBy('payment_method')->pluck('total', 'payment_method');

        $screens = $tonight->groupBy('screen')->map(fn ($shows, $screen) => [
            'screen' => $screen,
            'shows' => $shows->count(),
            'rate' => $shows->sum('seats') ? round($shows->sum('sold') / $shows->sum('seats') * 100) : 0,
        ])->values();

        $todayRevenue = (float) $paid()->whereDate('booked_at', $today)->sum(DB::raw('total_amount + gift_card_amount'));
        $yesterday = (float) $paid()->whereDate('booked_at', now()->subDay()->toDateString())->sum(DB::raw('total_amount + gift_card_amount'));

        return [
            'kpis' => [
                'revenue_today' => round($todayRevenue, 2),
                'revenue_change' => $yesterday > 0 ? round(($todayRevenue - $yesterday) / $yesterday * 100) : null,
                'tickets_today' => (int) $paid()->whereDate('booked_at', $today)->sum('seat_count'),
                'tonight_rate' => $tonight->sum('seats') ? round($tonight->sum('sold') / $tonight->sum('seats') * 100) : 0,
                'tonight_shows' => $tonight->count(),
                'members_week' => (int) $memberSeries->slice(7)->sum(),
                'members_total' => User::query()->count(),
            ],
            'series' => $series,
            'memberSeries' => $memberSeries,
            'tonight' => $tonight,
            'heat' => $heat,
            'top' => $top,
            'methods' => collect(['cod' => 'Counter', 'jazzcash' => 'JazzCash', 'easypaisa' => 'Easypaisa', 'card' => 'Card'])
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => (int) ($methods[$key] ?? 0)])->values(),
            'screens' => $screens,
            'attention' => array_values(array_filter([
                ['label' => 'reviews waiting for a decision', 'count' => Review::query()->where('is_approved', false)->count(), 'href' => route('admin.reviews'), 'tone' => 'volt'],
                ['label' => 'unpaid bookings for shows in the next 3 hours', 'count' => Booking::query()->where('booking_status', 'confirmed')->where('payment_status', '<>', 'paid')
                    ->whereHas('show', fn ($query) => $query->whereRaw('TIMESTAMP(show_date, show_time) BETWEEN ? AND ?', [now()->toDateTimeString(), now()->addHours(3)->toDateTimeString()]))->count(), 'href' => route('admin.activity'), 'tone' => 'signal'],
                ['label' => 'snacks low or sold out', 'count' => DB::table('concessions')->whereNotNull('stock')->whereColumn('stock', '<=', 'low_stock_at')->count(), 'href' => route('admin.commerce'), 'tone' => 'volt'],
                ['label' => 'people on show waitlists', 'count' => DB::table('show_waitlists')->whereNull('notified_at')->count(), 'href' => route('admin.movies'), 'tone' => 'mint'],
                ['label' => 'unread contact messages', 'count' => DB::table('contact_messages')->where('is_read', false)->count(), 'href' => route('admin.activity'), 'tone' => 'volt'],
                ['label' => 'coupons used up this week', 'count' => Coupon::query()->whereNotNull('max_uses')->whereColumn('used_count', '>=', 'max_uses')->where('updated_at', '>=', now()->subWeek())->count(), 'href' => route('admin.commerce'), 'tone' => 'mint'],
            ], fn ($item) => $item['count'] > 0)),
            'activity' => DB::table('audit_logs')->latest('id')->limit(10)->get(['action', 'actor_type', 'created_at'])
                ->map(fn ($row) => ['what' => Str::of($row->action)->replace(['.', '_'], ' ')->ucfirst()->value(), 'who' => $row->actor_type ?? 'system', 'ago' => Carbon::parse($row->created_at)->diffForHumans()])->values(),
        ];
    }

    /** Site-wide settings, page SEO and broadcasts (owner only). */
    public function content(Request $request): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            return redirect()->route('admin.login');
        }

        $settings = SiteSetting::query()->pluck('value', 'key')->all();

        return $this->page('Admin/Content', [
            'settings' => [
                'site_name' => $settings['site_name'] ?? 'BookMyMovie',
                'canonical_base_url' => $settings['canonical_base_url'] ?? 'https://bookmymovie.ahmershah.dev',
                'default_meta_title' => $settings['default_meta_title'] ?? 'BookMyMovie - Book Cinema Tickets Online',
                'default_meta_description' => $settings['default_meta_description'] ?? '',
                'support_email' => $settings['support_email'] ?? '',
                'home_ticker_messages' => $settings['home_ticker_messages'] ?? '',
            ],
            'contentPages' => ContentPage::query()->orderBy('slug')->get(['id', 'slug', 'title', 'meta_title', 'meta_description', 'canonical_path', 'excerpt'])
                ->map(fn (ContentPage $page) => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => (string) $page->title,
                    'meta_title' => (string) $page->meta_title,
                    'meta_description' => (string) $page->meta_description,
                    'canonical_path' => (string) $page->canonical_path,
                    'excerpt' => (string) $page->excerpt,
                ])->values(),
            'members' => User::query()->where('is_blocked', false)->count(),
        ], ['title' => 'Content & SEO | Admin', 'description' => 'Site settings, page SEO and broadcasts.', 'robots' => 'noindex, nofollow']);
    }

    public function movies(Request $request, ?int $movie = null): Response|RedirectResponse
    {
        $admin = $this->admin($request);

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $selectedMovie = Movie::query()->with('genres')->find($movie)
            ?: Movie::query()->with('genres')->latest()->first();

        return $this->page('Admin/Movies', [
            'admin' => ['name' => $admin->name, 'email' => $admin->email],
            'sessionMinutes' => (int) config('session.admin_lifetime', 60),
            'selectedMovie' => $selectedMovie ? [
                'id' => $selectedMovie->id,
                'title' => $selectedMovie->title,
                'slug' => $selectedMovie->slug,
                'language' => $selectedMovie->language,
                'duration_minutes' => (int) $selectedMovie->duration_minutes,
                'certificate_rating' => (string) $selectedMovie->certificate_rating,
                'status' => $selectedMovie->status,
                'release_date' => $selectedMovie->release_date?->format('Y-m-d') ?? '',
                'genre_ids' => $selectedMovie->genres->pluck('id')->values(),
                'base_price' => (string) $selectedMovie->base_price,
                'sale_price' => (string) ($selectedMovie->sale_price ?? ''),
                'tagline' => (string) $selectedMovie->tagline,
                'rating_mode' => $selectedMovie->rating_mode ?: 'real',
                'fake_average_rating' => (string) ($selectedMovie->fake_average_rating ?? ''),
                'fake_total_reviews' => (string) ($selectedMovie->fake_total_reviews ?? ''),
                'description' => (string) $selectedMovie->description,
                'hero_carousel_enabled' => (bool) $selectedMovie->hero_carousel_enabled,
                'hero_sort_order' => (string) ($selectedMovie->hero_sort_order ?? 0),
                'hero_eyebrow' => (string) $selectedMovie->hero_eyebrow,
                'hero_tagline' => (string) $selectedMovie->hero_tagline,
                'meta_title' => (string) $selectedMovie->meta_title,
                'meta_description' => (string) $selectedMovie->meta_description,
                'kids_discount_eligible' => (bool) $selectedMovie->kids_discount_eligible,
                'poster_url' => $selectedMovie->publicMediaUrl($selectedMovie->poster_image),
                'hero_url' => $selectedMovie->publicMediaUrl($selectedMovie->hero_image ?: $selectedMovie->banner_image),
            ] : null,
            'movieOptions' => Movie::query()->latest()->limit(100)->get(['id', 'title', 'rating_mode', 'hero_carousel_enabled'])
                ->map(fn (Movie $movie) => ['id' => $movie->id, 'label' => $movie->title.' — '.($movie->hero_carousel_enabled ? 'Hero' : 'Catalog').' — '.$movie->rating_mode.' rating'])
                ->values(),
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name']),
            'recentMovies' => Movie::query()->latest()->limit(8)->get()->map(fn (Movie $movie) => ['label' => $movie->title, 'value' => 'PKR '.number_format((float) ($movie->sale_price ?: $movie->base_price))])->all(),
            'trashedMovies' => Movie::onlyTrashed()->latest('deleted_at')->limit(12)->get(['id', 'title'])->map(fn (Movie $movie) => ['id' => $movie->id, 'title' => $movie->title])->values(),
            'schedule' => DB::table('v_show_details')->where('show_status', 'scheduled')->whereRaw('TIMESTAMP(show_date, show_time) > ?', [now()->toDateTimeString()])->orderBy('show_date')->orderBy('show_time')->limit(8)->get()
                ->map(fn ($show) => ['label' => "{$show->movie_title}", 'value' => "{$show->theater_name} · {$show->screen_name} · {$show->show_date} {$show->show_time}"])
                ->all(),
            'selectedMovieShows' => $selectedMovie
                ? DB::table('v_show_details')
                    ->where('movie_id', $selectedMovie->id)
                    ->where('show_status', 'scheduled')
                    ->whereDate('show_date', '>=', now()->toDateString())
                    ->orderBy('show_date')
                    ->orderBy('show_time')
                    ->limit(12)
                    ->get()
                    ->map(fn ($show) => [
                        'id' => $show->show_id,
                        'venue' => $show->theater_name.' — '.$show->screen_name,
                        'when' => Carbon::parse($show->show_date)->format('D, M j, Y').' · '.Carbon::parse($show->show_time)->format('h:i A'),
                        'available' => (int) $show->available_seats,
                        'booked' => (int) $show->booked_seats,
                    ])
                : [],
            'rowPriceBenefits' => $selectedMovie && Schema::hasTable('show_seat_row_prices')
                ? DB::table('show_seat_row_prices')
                    ->join('shows', 'show_seat_row_prices.show_id', '=', 'shows.id')
                    ->where('shows.movie_id', $selectedMovie->id)
                    ->where('shows.status', 'scheduled')
                    ->orderBy('show_seat_row_prices.row_label')
                    ->select('show_seat_row_prices.*')
                    ->get()
                    ->unique('row_label')
                    ->values()
                    ->map(fn ($tier) => [
                        'row' => $tier->row_label,
                        'tier' => $tier->tier_name,
                        'price' => 'PKR '.number_format((float) ($tier->sale_price ?: $tier->price)),
                        'benefits' => $tier->benefits,
                    ])
                : [],
            'certificates' => ['U', 'UA', 'A', 'S', 'G', 'PG', 'PG-13', 'R'],
            'today' => now()->toDateString(),
        ], ['title' => 'Films & shows | Admin', 'description' => 'Catalogue, media, hero carousel and show pricing.', 'robots' => 'noindex, nofollow']);
    }

    public function handleDashboard(Request $request): RedirectResponse
    {
        $admin = $this->admin($request);

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        AuditLog::record('admin.'.((string) $request->input('_action') ?: 'unknown'), null, $request->except(['_token', 'poster_upload', 'hero_upload', 'password', 'pages']), $request, 'admin', $admin->id);

        return match ($request->input('_action')) {
            'store_movie' => $this->storeMovie($request, $admin),
            'update_movie' => $this->updateMovie($request),
            'delete_movie' => $this->deleteMovie($request),
            'restore_movie' => $this->restoreMovie($request),
            'store_coupon' => $this->storeCoupon($request, $admin),
            'send_notification' => $this->sendNotification($request),
            'update_settings' => $this->updateSettings($request),
            'update_pages' => $this->updatePages($request),
            'update_profile' => $this->updateProfile($request, $admin),
            default => back()->withErrors(['action' => 'Unknown admin action.']),
        };
    }

    private function storeMovie(Request $request, Admin $admin): RedirectResponse
    {
        $data = $this->validatedMovieData($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['description'] = $data['description'] ?: 'Added from admin dashboard.';
        $data['created_by'] = $admin->id;

        foreach (['poster_image' => 'poster_upload', 'hero_image' => 'hero_upload'] as $column => $field) {
            if ($uploaded = $this->storeMovieUpload($request, $field, $column)) {
                $data[$column] = $uploaded;
            }
        }

        if (! empty($data['hero_image'])) {
            $data['banner_image'] = $data['hero_image'];
        }

        $movie = Movie::create($data);
        $movie->genres()->sync($request->input('genre_ids', []));

        return back()->with('status', 'Movie saved.');
    }

    private function updateMovie(Request $request): RedirectResponse
    {
        $movie = Movie::findOrFail($request->integer('movie_id'));

        $data = $this->validatedMovieData($request, $movie);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        foreach (['poster_image', 'hero_image'] as $column) {
            if ($request->boolean("delete_{$column}")) {
                $this->deleteStoredMovieImage($movie->{$column});
                $data[$column] = null;
            }
        }

        foreach (['poster_image' => 'poster_upload', 'hero_image' => 'hero_upload'] as $column => $field) {
            if ($uploaded = $this->storeMovieUpload($request, $field, $column)) {
                $this->deleteStoredMovieImage($movie->{$column});
                $data[$column] = $uploaded;
            }
        }

        if (array_key_exists('hero_image', $data)) {
            if ($movie->banner_image && $movie->banner_image !== $movie->hero_image) {
                $this->deleteStoredMovieImage($movie->banner_image);
            }

            $data['banner_image'] = $data['hero_image'];
        }

        $movie->update($data);
        $movie->genres()->sync($request->input('genre_ids', []));

        return redirect()
            ->route('admin.movies', $movie->id)
            ->with('status', 'Movie details updated.');
    }

    private function deleteMovie(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'movie_id' => ['required', 'integer', 'exists:movies,id'],
        ]);

        Movie::findOrFail($data['movie_id'])->delete();

        return redirect()->route('admin.movies')->with('status', 'Movie removed from the active catalog.');
    }

    private function restoreMovie(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'movie_id' => ['required', 'integer'],
        ]);

        $movie = Movie::onlyTrashed()->findOrFail($data['movie_id']);
        $movie->restore();

        return redirect()->route('admin.movies', $movie->id)->with('status', 'Movie restored.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedMovieData(Request $request, ?Movie $movie = null): array
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:230', Rule::unique('movies', 'slug')->ignore($movie?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'language' => ['required', 'string', 'max:50'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'certificate_rating' => ['nullable', Rule::in(['U', 'UA', 'A', 'S', 'G', 'PG', 'PG-13', 'R'])],
            'release_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['coming_soon', 'now_showing', 'ended'])],
            // Optional: films without artwork get a generated typographic poster.
            'poster_upload' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
            'hero_carousel_enabled' => ['nullable', 'boolean'],
            'hero_sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'hero_eyebrow' => ['nullable', 'string', 'max:80'],
            'hero_tagline' => ['nullable', 'string', 'max:220'],
            'hero_upload' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
            'tagline' => ['nullable', 'string', 'max:180'],
            'studio' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'content_advisory' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:150'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999', 'lte:base_price'],
            'kids_discount_eligible' => ['nullable', 'boolean'],
            'rating_mode' => ['required', Rule::in(['real', 'fake'])],
            'fake_average_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'fake_total_reviews' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
            'delete_poster_image' => ['nullable', 'boolean'],
            'delete_hero_image' => ['nullable', 'boolean'],
        ], [
            'poster_upload.required' => 'Upload a 4:3 card image for this movie.',
            'poster_upload.max' => 'Card image must be 3 MB or smaller.',
            'hero_upload.required' => 'Upload a 16:9 carousel image for this movie.',
            'hero_upload.max' => 'Carousel image must be 3 MB or smaller.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! $this->imageHasAspect($request, 'poster_upload', 4 / 3)) {
                $validator->errors()->add('poster_upload', 'Card image must use a 4:3 landscape aspect ratio.');
            }

            if (! $this->imageHasAspect($request, 'hero_upload', 16 / 9)) {
                $validator->errors()->add('hero_upload', 'Carousel image must use a 16:9 widescreen aspect ratio.');
            }
        });

        $validated = $validator->validate();

        $validated['hero_carousel_enabled'] = $request->boolean('hero_carousel_enabled');
        $validated['kids_discount_eligible'] = $request->boolean('kids_discount_eligible');
        $validated['hero_sort_order'] = $validated['hero_sort_order'] ?? 0;

        return Arr::except($validated, [
            'poster_upload',
            'hero_upload',
            'genre_ids',
            'delete_poster_image',
            'delete_hero_image',
        ]);
    }

    private function imageHasAspect(Request $request, string $field, float $expectedRatio): bool
    {
        if (! $request->hasFile($field) || ! $request->file($field)->isValid()) {
            return true;
        }

        $size = getimagesize($request->file($field)->getRealPath());

        if (! $size || empty($size[1])) {
            return true;
        }

        $actualRatio = $size[0] / $size[1];

        return abs($actualRatio - $expectedRatio) <= 0.02;
    }

    private function storeMovieUpload(Request $request, string $field, string $column): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        try {
            return app(MovieImageProcessor::class)->storeAsWebp(
                $request->file($field),
                'movie-media/'.Str::of($column)->before('_image')->plural()
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }
    }

    private function deleteStoredMovieImage(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://', '//', 'images/'])) {
            return;
        }

        $path = Str::of($path)->replaceStart('/storage/', '')->replaceStart('storage/', '')->toString();

        if (Str::startsWith($path, 'movie-media/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function storeCoupon(Request $request, Admin $admin): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:coupons,code'],
            'discount' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        Coupon::create([
            'code' => strtoupper($data['code']),
            'description' => 'Admin created coupon',
            'discount_type' => 'percentage',
            'discount_value' => $data['discount'],
            'min_order_amount' => 0,
            'max_uses_per_user' => 1,
            'valid_from' => now(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        return back()->with('status', 'Coupon created.');
    }

    private function sendNotification(Request $request): RedirectResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);

        // One multi-row INSERT per 500 members instead of a query per member.
        $at = now();
        User::query()->where('is_blocked', false)->select('id')->chunkById(500, function ($users) use ($data, $at) {
            Notification::query()->insert($users->map(fn ($user) => [
                'user_id' => $user->id,
                'type' => 'admin_message',
                'title' => 'BookMyMovie update',
                'message' => strip_tags($data['message']),
                'is_read' => false,
                'created_at' => $at,
            ])->all());
        });

        return back()->with('status', 'Notification sent.');
    }

    private function updateProfile(Request $request, Admin $admin): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('admins', 'email')->ignore($admin->id)],
            'current_password' => ['nullable', 'string', 'max:72'],
            'password' => ['nullable', 'string', 'max:72', \Illuminate\Validation\Rules\Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        // A stolen session must not be able to take over the account.
        if ((! empty($data['password']) || strtolower($data['email']) !== $admin->email) && ! Hash::check((string) ($data['current_password'] ?? ''), $admin->password)) {
            return back()->withErrors(['current_password' => 'Enter your current password to change your email or password.']);
        }

        $admin->name = $data['name'];
        $admin->email = $data['email'];

        if (! empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }

        $admin->save();

        return back()->with('status', 'Admin profile updated.');
    }

    private function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'default_meta_title' => ['required', 'string', 'max:60'],
            'default_meta_description' => ['required', 'string', 'max:150'],
            'canonical_base_url' => ['required', 'url', 'max:180'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'home_ticker_messages' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($data as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => 'string',
                    'is_public' => true,
                ]
            );
        }

        return back()->with('status', 'Website settings updated.');
    }

    private function updatePages(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pages' => ['required', 'array'],
            'pages.*.id' => ['required', 'integer', 'exists:content_pages,id'],
            'pages.*.title' => ['required', 'string', 'max:200'],
            'pages.*.meta_title' => ['nullable', 'string', 'max:60'],
            'pages.*.meta_description' => ['nullable', 'string', 'max:150'],
            'pages.*.canonical_path' => ['nullable', 'string', 'max:220'],
            'pages.*.excerpt' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($data['pages'] as $pageData) {
            ContentPage::whereKey($pageData['id'])->update([
                'title' => $pageData['title'],
                'meta_title' => $pageData['meta_title'] ?? null,
                'meta_description' => $pageData['meta_description'] ?? null,
                'canonical_path' => $pageData['canonical_path'] ?? null,
                'excerpt' => $pageData['excerpt'] ?? null,
            ]);
        }

        return back()->with('status', 'Page SEO updated.');
    }

    protected function admin(Request $request): ?Admin
    {
        $id = $request->session()->get('admin_id');

        if (! $id) {
            return null;
        }

        $authenticatedAt = (int) $request->session()->get('admin_authenticated_at', 0);
        $lifetimeSeconds = (int) config('session.admin_lifetime', 60) * 60;

        if ($authenticatedAt === 0 || now()->timestamp - $authenticatedAt > $lifetimeSeconds) {
            $request->session()->forget(['admin_id', 'admin_authenticated_at']);
            $request->session()->regenerateToken();

            return null;
        }

        // Deactivating an admin ends their access on the very next request.
        return Admin::query()->whereKey($id)->where('is_active', true)->first();
    }
}
