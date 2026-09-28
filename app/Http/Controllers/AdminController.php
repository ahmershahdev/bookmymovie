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
    public function dashboard(Request $request, ?int $movie = null): Response|RedirectResponse
    {
        $admin = $this->admin($request);

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $stats = [
            'revenue' => (float) Booking::query()->where('booking_status', '<>', 'cancelled')->sum('total_amount'),
            'bookings' => Booking::query()->count(),
            'users' => User::query()->count(),
            'topMovie' => DB::table('v_movie_stats')->orderByDesc('tickets_sold')->value('title') ?: 'N/A',
            'activeShows' => DB::table('v_show_details')->where('show_status', 'scheduled')->count(),
            'pendingReviews' => Review::query()->where('is_approved', false)->count(),
        ];
        $selectedMovie = Movie::query()->with('genres')->find($movie)
            ?: Movie::query()->with('genres')->latest()->first();

        $settings = SiteSetting::query()->pluck('value', 'key')->all();

        return $this->page('Admin/Dashboard', [
            'admin' => ['name' => $admin->name, 'email' => $admin->email],
            'sessionMinutes' => (int) config('session.admin_lifetime', 60),
            'stats' => $stats,
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
            'schedule' => DB::table('v_show_details')->orderBy('show_date')->orderBy('show_time')->limit(8)->get()
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
            'bookings' => Booking::query()->latest('booked_at')->limit(8)->get(['booking_number', 'total_amount', 'booking_status'])
                ->map(fn (Booking $booking) => ['label' => $booking->booking_number, 'value' => ucfirst($booking->booking_status).' · PKR '.number_format((float) $booking->total_amount)])->all(),
            'coupons' => Coupon::query()->latest()->limit(8)->get(['code', 'discount_type', 'discount_value'])
                ->map(fn (Coupon $coupon) => ['label' => $coupon->code, 'value' => $coupon->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $coupon->discount_value, 2), '0'), '.').'% off' : 'PKR '.number_format((float) $coupon->discount_value).' off'])->all(),
            'users' => User::query()->latest()->limit(8)->get(['name', 'created_at'])
                ->map(fn (User $user) => ['label' => $user->name, 'value' => 'Joined '.$user->created_at?->format('j M Y')])->all(),
            'customers' => DB::table('v_user_stats')->orderByDesc('total_spent')->limit(8)->get()
                ->map(fn ($user) => ['label' => $user->name, 'value' => 'PKR '.number_format((float) $user->total_spent)])
                ->all(),
            'reviews' => Review::query()->with('movie:id,title')->latest()->limit(8)->get()
                ->map(fn (Review $review) => ['label' => '#'.$review->id.' · '.($review->movie?->title ?? 'Film'), 'value' => $review->rating.' ★'.($review->is_approved ? '' : ' · pending')])
                ->all(),
            'certificates' => ['U', 'UA', 'A', 'S', 'G', 'PG', 'PG-13', 'R'],
            'today' => now()->toDateString(),
        ], ['title' => 'Admin Dashboard | BookMyMovie', 'description' => 'Manage BookMyMovie movies, pricing, bookings, users, content, SEO, and site settings.', 'robots' => 'noindex, nofollow']);
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
            ->route('admin.dashboard.movie', $movie->id)
            ->with('status', 'Movie details updated.');
    }

    private function deleteMovie(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'movie_id' => ['required', 'integer', 'exists:movies,id'],
        ]);

        Movie::findOrFail($data['movie_id'])->delete();

        return redirect()->route('admin.dashboard')->with('status', 'Movie removed from the active catalog.');
    }

    private function restoreMovie(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'movie_id' => ['required', 'integer'],
        ]);

        $movie = Movie::onlyTrashed()->findOrFail($data['movie_id']);
        $movie->restore();

        return redirect()->route('admin.dashboard.movie', $movie->id)->with('status', 'Movie restored.');
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
            'poster_upload.required' => 'Upload a 9:16 poster image for this movie.',
            'poster_upload.max' => 'Poster image must be 3 MB or smaller.',
            'hero_upload.required' => 'Upload a 16:9 carousel image for this movie.',
            'hero_upload.max' => 'Carousel image must be 3 MB or smaller.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! $this->imageHasAspect($request, 'poster_upload', 9 / 16)) {
                $validator->errors()->add('poster_upload', 'Poster image must use a 9:16 vertical aspect ratio.');
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

        User::query()->select('id')->chunkById(100, function ($users) use ($data) {
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'admin_message',
                    'title' => 'BookMyMovie update',
                    'message' => $data['message'],
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }
        });

        return back()->with('status', 'Notification sent.');
    }

    private function updateProfile(Request $request, Admin $admin): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('admins', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

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

    private function admin(Request $request): ?Admin
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
