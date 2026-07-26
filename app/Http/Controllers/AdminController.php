<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\ContentPage;
use App\Models\Coupon;
use App\Models\Movie;
use App\Models\Notification;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
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
        $selectedMovie = Movie::query()->with('genres')->find($request->integer('movie_id'))
            ?: Movie::query()->with('genres')->latest()->first();

        return view('admin.dashboard', [
            'admin' => $admin,
            'stats' => $stats,
            'settings' => SiteSetting::query()->pluck('value', 'key')->all(),
            'contentPages' => ContentPage::query()->orderBy('slug')->get(),
            'selectedMovie' => $selectedMovie,
            'movieEditorList' => Movie::query()->latest()->limit(100)->get(['id', 'title', 'slug', 'base_price', 'sale_price', 'rating_mode', 'hero_carousel_enabled']),
            'genres' => \App\Models\Genre::query()->orderBy('name')->get(['id', 'name']),
            'moviesList' => Movie::query()->latest()->limit(8)->get()->map(fn (Movie $movie) => "{$movie->title} - PKR ".number_format((float) ($movie->sale_price ?: $movie->base_price)))->all(),
            'trashedMovies' => Movie::onlyTrashed()->latest('deleted_at')->limit(12)->get(['id', 'title', 'deleted_at']),
            'showsList' => DB::table('v_show_details')->orderBy('show_date')->orderBy('show_time')->limit(8)->get()
                ->map(fn ($show) => "{$show->theater_name} - {$show->screen_name} - {$show->movie_title} {$show->show_date} {$show->show_time}")
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
                : collect(),
            'rowPriceBenefits' => $selectedMovie && \Illuminate\Support\Facades\Schema::hasTable('show_seat_row_prices')
                ? DB::table('show_seat_row_prices')
                    ->join('shows', 'show_seat_row_prices.show_id', '=', 'shows.id')
                    ->where('shows.movie_id', $selectedMovie->id)
                    ->where('shows.status', 'scheduled')
                    ->orderBy('show_seat_row_prices.row_label')
                    ->select('show_seat_row_prices.*')
                    ->get()
                    ->unique('row_label')
                    ->values()
                : collect(),
            'bookingsList' => Booking::query()->latest('booked_at')->limit(8)->pluck('booking_number')->all(),
            'couponsList' => Coupon::query()->latest()->limit(8)->pluck('code')->all(),
            'usersList' => User::query()->latest()->limit(8)->pluck('name')->all(),
            'customerList' => DB::table('v_user_stats')->orderByDesc('total_spent')->limit(8)->get()
                ->map(fn ($user) => "{$user->name} - PKR ".number_format((float) $user->total_spent))
                ->all(),
            'reviewsList' => Review::query()->with('user')->latest()->limit(8)->get()
                ->map(fn (Review $review) => "Review #{$review->id} - {$review->rating} stars")
                ->all(),
        ]);
    }

    public function handleDashboard(Request $request): RedirectResponse
    {
        $admin = $this->admin($request);

        if (! $admin) {
            return redirect()->route('admin.login');
        }

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

        foreach (['poster_image' => 'poster_upload', 'banner_image' => 'banner_upload', 'hero_image' => 'hero_upload'] as $column => $field) {
            if ($uploaded = $this->storeMovieUpload($request, $field, $column)) {
                $data[$column] = $uploaded;
            }
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

        foreach (['poster_image', 'banner_image', 'hero_image'] as $column) {
            if ($request->boolean("delete_{$column}")) {
                $this->deleteStoredMovieImage($movie->{$column});
                $data[$column] = null;
            }
        }

        foreach (['poster_image' => 'poster_upload', 'banner_image' => 'banner_upload', 'hero_image' => 'hero_upload'] as $column => $field) {
            if ($uploaded = $this->storeMovieUpload($request, $field, $column)) {
                $this->deleteStoredMovieImage($movie->{$column});
                $data[$column] = $uploaded;
            }
        }

        $movie->update($data);
        $movie->genres()->sync($request->input('genre_ids', []));

        return redirect()
            ->route('admin.dashboard', ['movie_id' => $movie->id])
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

        return redirect()->route('admin.dashboard', ['movie_id' => $movie->id])->with('status', 'Movie restored.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedMovieData(Request $request, ?Movie $movie = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:230', Rule::unique('movies', 'slug')->ignore($movie?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'language' => ['required', 'string', 'max:50'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'certificate_rating' => ['nullable', Rule::in(['U', 'UA', 'A', 'S', 'G', 'PG', 'PG-13', 'R'])],
            'release_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['coming_soon', 'now_showing', 'ended'])],
            'poster_image' => ['nullable', 'string', 'max:255'],
            'poster_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'banner_image' => ['nullable', 'string', 'max:255'],
            'banner_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'hero_carousel_enabled' => ['nullable', 'boolean'],
            'hero_sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'hero_eyebrow' => ['nullable', 'string', 'max:80'],
            'hero_tagline' => ['nullable', 'string', 'max:220'],
            'hero_image' => ['nullable', 'string', 'max:255'],
            'hero_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'trailer_url' => ['nullable', 'url', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'kids_discount_eligible' => ['nullable', 'boolean'],
            'rating_mode' => ['required', Rule::in(['real', 'fake'])],
            'fake_average_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'fake_total_reviews' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
            'delete_poster_image' => ['nullable', 'boolean'],
            'delete_banner_image' => ['nullable', 'boolean'],
            'delete_hero_image' => ['nullable', 'boolean'],
        ]);

        $validated['hero_carousel_enabled'] = $request->boolean('hero_carousel_enabled');
        $validated['kids_discount_eligible'] = $request->boolean('kids_discount_eligible');
        $validated['hero_sort_order'] = $validated['hero_sort_order'] ?? 0;

        return Arr::except($validated, [
            'poster_upload',
            'banner_upload',
            'hero_upload',
            'genre_ids',
            'delete_poster_image',
            'delete_banner_image',
            'delete_hero_image',
        ]);
    }

    private function storeMovieUpload(Request $request, string $field, string $column): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store('movie-media/'.Str::of($column)->before('_image')->plural(), 'public');
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
            'default_meta_description' => ['required', 'string', 'max:160'],
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
            'pages.*.meta_description' => ['nullable', 'string', 'max:160'],
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

        return Admin::find($id);
    }
}
