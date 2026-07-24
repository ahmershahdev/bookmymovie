<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Movie;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        ];

        return view('admin.dashboard', [
            'admin' => $admin,
            'stats' => $stats,
            'moviesList' => Movie::query()->latest()->limit(8)->pluck('title')->all(),
            'trashedMovies' => Movie::onlyTrashed()->latest('deleted_at')->limit(8)->pluck('title')->all(),
            'showsList' => DB::table('v_show_details')->orderBy('show_date')->orderBy('show_time')->limit(8)->get()
                ->map(fn ($show) => "{$show->theater_name} - {$show->screen_name} - {$show->movie_title} {$show->show_date} {$show->show_time}")
                ->all(),
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

        if ($request->filled('title') || $request->filled('slug')) {
            return $this->storeMovie($request, $admin);
        }

        if ($request->filled('code') || $request->filled('discount')) {
            return $this->storeCoupon($request, $admin);
        }

        if ($request->filled('message')) {
            return $this->sendNotification($request);
        }

        return $this->updateProfile($request, $admin);
    }

    private function storeMovie(Request $request, Admin $admin): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:230', 'unique:movies,slug'],
        ]);

        Movie::create([
            'title' => $data['title'],
            'slug' => $data['slug'] ?: Str::slug($data['title']),
            'description' => 'Added from admin dashboard.',
            'language' => 'English',
            'duration_minutes' => 120,
            'certificate_rating' => 'UA',
            'release_date' => now()->toDateString(),
            'status' => 'coming_soon',
            'created_by' => $admin->id,
        ]);

        return back()->with('status', 'Movie saved.');
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

    private function admin(Request $request): ?Admin
    {
        $id = $request->session()->get('admin_id');

        return $id ? Admin::find($id) : null;
    }
}
