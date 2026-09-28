<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\BannedDevice;
use App\Models\BannedIp;
use App\Models\Booking;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AuditLog;
use App\Support\DeviceIdentity;
use App\Support\LayoutData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

/**
 * The back office beyond the catalogue: members (with account and IP bans),
 * review moderation, and the site's brand, contact and social details.
 */
class AdminPortalController extends AdminController
{
    public function users(Request $request): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            return redirect()->route('admin.login');
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:all,active,banned'],
        ]);
        $q = trim((string) ($filters['q'] ?? ''));

        $users = User::query()
            ->withCount(['bookings', 'reviews'])
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$q}%")
                ->orWhere('username', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('last_login_ip', $q)))
            ->when(($filters['status'] ?? 'all') === 'banned', fn (Builder $query) => $query->where('is_blocked', true))
            ->when(($filters['status'] ?? 'all') === 'active', fn (Builder $query) => $query->where('is_blocked', false))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return $this->page('Admin/Users', [
            'users' => $users->through(fn (User $user) => $this->userRow($user)),
            'filters' => ['q' => $q, 'status' => $filters['status'] ?? 'all'],
            'bannedIps' => BannedIp::query()->with('user:id,username')->latest()->limit(50)->get()->map(fn (BannedIp $ban) => [
                'id' => $ban->id,
                'ip' => $ban->ip_address,
                'reason' => $ban->reason,
                'user' => $ban->user?->username,
                'expires' => $ban->expires_at?->format('j M Y'),
                'active' => $ban->expires_at === null || $ban->expires_at->isFuture(),
                'at' => $ban->created_at?->format('j M Y'),
            ])->values(),
            'bannedDevices' => BannedDevice::query()->with('user:id,username')->latest()->limit(50)->get()->map(fn (BannedDevice $ban) => [
                'id' => $ban->id,
                'label' => $ban->label ?? 'Browser',
                'hash' => substr($ban->device_hash, 0, 10),
                'reason' => $ban->reason,
                'user' => $ban->user?->username,
                'expires' => $ban->expires_at?->format('j M Y'),
                'active' => $ban->expires_at === null || $ban->expires_at->isFuture(),
                'at' => $ban->created_at?->format('j M Y'),
            ])->values(),
            'counts' => [
                'total' => User::query()->count(),
                'banned' => User::query()->where('is_blocked', true)->count(),
                'ips' => BannedIp::query()->active()->count(),
                'devices' => BannedDevice::query()->active()->count(),
            ],
        ], ['title' => 'Members | Admin', 'description' => 'Members, bans and IP blocks.', 'robots' => 'noindex, nofollow']);
    }

    public function userShow(Request $request, User $user): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            return redirect()->route('admin.login');
        }

        $user->loadCount(['bookings', 'reviews']);

        return $this->page('Admin/UserShow', [
            'member' => [
                ...$this->userRow($user),
                'phone' => $user->phone,
                'city' => $user->city,
                'bio' => $user->bio,
                'blocked_reason' => $user->blocked_reason,
                'blocked_at' => $user->blocked_at?->format('j M Y, H:i'),
                'loyalty_points' => (int) $user->loyalty_points,
                'two_factor' => (bool) $user->two_factor_enabled,
                'ip_banned' => BannedIp::isBanned($user->last_login_ip),
                'spent' => (float) Booking::query()->where('user_id', $user->id)->where('booking_status', '<>', 'cancelled')->sum('total_amount'),
            ],
            'bookings' => Booking::query()->with('show.movie:id,title')->where('user_id', $user->id)->latest('booked_at')->limit(12)->get()->map(fn (Booking $booking) => [
                'number' => $booking->booking_number,
                'film' => $booking->show?->movie?->title,
                'at' => $booking->booked_at?->format('j M Y'),
                'total' => (float) $booking->total_amount,
                'status' => $booking->booking_status,
            ])->values(),
            'reviews' => Review::query()->with('movie:id,title,slug')->where('user_id', $user->id)->latest()->limit(12)->get()->map(fn (Review $review) => [
                'id' => $review->id,
                'film' => $review->movie?->title,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'text' => $review->review_text,
                'approved' => (bool) $review->is_approved,
                'verified' => $review->isVerified(),
            ])->values(),
            'devices' => DB::table('user_devices')->where('user_id', $user->id)->orderByDesc('last_seen_at')->limit(10)->get()
                ->map(fn ($device) => [
                    'label' => DeviceIdentity::label($device->user_agent),
                    'ip' => $device->ip_address,
                    'first' => \Illuminate\Support\Carbon::parse($device->first_seen_at)->format('j M Y'),
                    'last' => \Illuminate\Support\Carbon::parse($device->last_seen_at)->diffForHumans(),
                    'banned' => BannedDevice::isBanned($device->device_hash),
                ])->values(),
            // Other accounts on the same browsers: the usual sign of ban evasion.
            'linked' => User::query()
                ->whereKeyNot($user->id)
                ->whereIn('id', DB::table('user_devices')->whereIn('device_hash', DB::table('user_devices')->where('user_id', $user->id)->select('device_hash'))->select('user_id'))
                ->limit(10)->get(['id', 'username', 'name', 'is_blocked'])
                ->map(fn (User $other) => ['id' => $other->id, 'username' => $other->username, 'name' => $other->name, 'banned' => (bool) $other->is_blocked])->values(),
            'logins' => DB::table('audit_logs')->where('actor_type', 'user')->where('actor_id', $user->id)->where('action', 'auth.login')->latest('id')->limit(8)->get(['ip_address', 'user_agent', 'created_at'])
                ->map(fn ($row) => ['ip' => $row->ip_address, 'agent' => $row->user_agent, 'at' => \Illuminate\Support\Carbon::parse($row->created_at)->format('j M Y, H:i')])->values(),
        ], ['title' => '@'.$user->username.' | Admin', 'description' => 'Member detail.', 'robots' => 'noindex, nofollow']);
    }

    public function banUser(Request $request, User $user): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:4', 'max:200'],
            // Both default to on: a ban should follow the person, not just the account.
            'ban_ip' => ['nullable', 'boolean'],
            'ban_devices' => ['nullable', 'boolean'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);
        $banIps = (bool) ($data['ban_ip'] ?? true);
        $banDevices = (bool) ($data['ban_devices'] ?? true);
        $until = isset($data['days']) ? now()->addDays((int) $data['days']) : null;

        [$ips, $devices] = DB::transaction(function () use ($user, $data, $admin, $banIps, $banDevices, $until, $request) {
            User::query()->whereKey($user->id)->lockForUpdate()->first();
            $user->forceFill(['is_blocked' => true, 'blocked_reason' => $data['reason'], 'blocked_at' => now()])->save();

            // End every session the member has open, on every device, and
            // kill "remember me" so no cookie can bring them back.
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->saveQuietly();

            $ips = $banIps ? $this->knownIps($user)->reject(fn (string $ip) => $ip === $request->ip())->values() : collect();
            foreach ($ips as $ip) {
                BannedIp::query()->updateOrCreate(['ip_address' => $ip], ['reason' => $data['reason'], 'banned_by' => $admin->id, 'user_id' => $user->id, 'expires_at' => $until]);
            }

            $devices = $banDevices ? DB::table('user_devices')->where('user_id', $user->id)->get(['device_hash', 'user_agent']) : collect();
            foreach ($devices as $device) {
                BannedDevice::query()->updateOrCreate(['device_hash' => $device->device_hash], [
                    'reason' => $data['reason'], 'banned_by' => $admin->id, 'user_id' => $user->id,
                    'label' => DeviceIdentity::label($device->user_agent), 'expires_at' => $until,
                ]);
            }

            return [$ips, $devices];
        }, 3);

        Cache::forget(BannedIp::CACHE_KEY);
        Cache::forget(BannedDevice::CACHE_KEY);
        AuditLog::record('admin.user.ban', $user, ['reason' => $data['reason'], 'ips' => $ips->all(), 'devices' => $devices->count(), 'days' => $data['days'] ?? null], $request, 'admin', $admin->id);

        $extra = collect([
            $ips->count() ? $ips->count().' '.\Illuminate\Support\Str::plural('IP', $ips->count()) : null,
            $devices->count() ? $devices->count().' '.\Illuminate\Support\Str::plural('device', $devices->count()) : null,
        ])->filter()->implode(' and ');

        return back()->with('status', '@'.$user->username.' is banned and signed out everywhere'.($extra ? ', with '.$extra.' blocked' : '').'.');
    }

    public function unbanUser(Request $request, User $user): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);

        DB::transaction(function () use ($user) {
            $user->forceFill(['is_blocked' => false, 'blocked_reason' => null, 'blocked_at' => null])->save();
            // Lift the network and device bans that came with this account's ban.
            BannedIp::query()->where('user_id', $user->id)->delete();
            BannedDevice::query()->where('user_id', $user->id)->delete();
        });

        Cache::forget(BannedIp::CACHE_KEY);
        Cache::forget(BannedDevice::CACHE_KEY);
        AuditLog::record('admin.user.unban', $user, [], $request, 'admin', $admin->id);

        return back()->with('status', '@'.$user->username.' can sign in again, and their IPs and devices are unblocked.');
    }

    public function unbanDevice(Request $request, BannedDevice $device): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        AuditLog::record('admin.device.unban', null, ['device' => substr($device->device_hash, 0, 12)], $request, 'admin', $admin->id);
        $device->delete();

        return back()->with('status', 'Device unblocked.');
    }

    /**
     * Every IP this member has been seen on: devices, sign-in audit entries
     * and the last sign-in, newest first, capped so a ban stays targeted.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function knownIps(User $user): \Illuminate\Support\Collection
    {
        return collect([$user->last_login_ip])
            ->merge(DB::table('user_devices')->where('user_id', $user->id)->orderByDesc('last_seen_at')->limit(10)->pluck('ip_address'))
            ->merge(DB::table('audit_logs')->where('actor_type', 'user')->where('actor_id', $user->id)->where('action', 'auth.login')->latest('id')->limit(10)->pluck('ip_address'))
            ->filter(fn ($ip) => is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP))
            ->unique()
            ->take(10)
            ->values();
    }

    public function banIp(Request $request): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $data = $request->validate([
            'ip' => ['required', 'ip'],
            'reason' => ['required', 'string', 'min:4', 'max:200'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        if ($data['ip'] === $request->ip()) {
            return back()->withErrors(['ip' => 'That is your own IP address. Banning it would lock you out.']);
        }

        $this->storeIpBan($data['ip'], $data['reason'], $admin, null, $data['days'] ?? null);
        AuditLog::record('admin.ip.ban', null, ['ip' => $data['ip'], 'reason' => $data['reason']], $request, 'admin', $admin->id);

        return back()->with('status', $data['ip'].' is blocked.');
    }

    public function unbanIp(Request $request, BannedIp $ban): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        AuditLog::record('admin.ip.unban', null, ['ip' => $ban->ip_address], $request, 'admin', $admin->id);
        $ban->delete();

        return back()->with('status', $ban->ip_address.' is unblocked.');
    }

    public function reviews(Request $request): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            return redirect()->route('admin.login');
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'in:all,pending,flagged,hidden'],
            'stars' => ['nullable', 'integer', 'between:1,5'],
        ]);
        $q = trim((string) ($filters['q'] ?? ''));
        $state = $filters['state'] ?? 'all';

        $reviews = Review::query()
            ->with(['user:id,name,username,is_blocked', 'movie:id,title,slug'])
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('review_text', 'like', "%{$q}%")
                ->orWhere('title', 'like', "%{$q}%")
                ->orWhereHas('user', fn (Builder $u) => $u->where('username', 'like', "%{$q}%"))
                ->orWhereHas('movie', fn (Builder $m) => $m->where('title', 'like', "%{$q}%"))))
            ->when($state === 'pending', fn (Builder $query) => $query->where('is_approved', false)->where('is_flagged', false))
            ->when($state === 'flagged', fn (Builder $query) => $query->where('is_flagged', true))
            ->when($state === 'hidden', fn (Builder $query) => $query->where('is_approved', false))
            ->when($filters['stars'] ?? null, fn (Builder $query, $stars) => $query->where('rating', $stars))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return $this->page('Admin/Reviews', [
            'reviews' => $reviews->through(fn (Review $review) => [
                'id' => $review->id,
                'film' => $review->movie?->title,
                'film_slug' => $review->movie?->slug,
                'author' => $review->user?->name,
                'username' => $review->user?->username,
                'user_id' => $review->user_id,
                'author_banned' => (bool) $review->user?->is_blocked,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'text' => $review->review_text,
                'approved' => (bool) $review->is_approved,
                'flagged' => (bool) $review->is_flagged,
                'verified' => $review->isVerified(),
                'helpful' => (int) $review->helpful_count,
                'at' => $review->created_at?->format('j M Y'),
            ]),
            'filters' => ['q' => $q, 'state' => $state, 'stars' => $filters['stars'] ?? null],
            'counts' => [
                'total' => Review::query()->count(),
                'pending' => Review::query()->where('is_approved', false)->where('is_flagged', false)->count(),
                'flagged' => Review::query()->where('is_flagged', true)->count(),
                'verified' => Review::query()->whereNotNull('booking_id')->count(),
            ],
        ], ['title' => 'Reviews | Admin', 'description' => 'Review moderation.', 'robots' => 'noindex, nofollow']);
    }

    public function moderateReview(Request $request, Review $review): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $data = $request->validate(['action' => ['required', 'in:approve,hide,flag,unflag,delete']]);

        match ($data['action']) {
            'approve' => $review->forceFill(['is_approved' => true, 'is_flagged' => false, 'approved_by' => $admin->id, 'approved_at' => now()])->save(),
            'hide' => $review->forceFill(['is_approved' => false])->save(),
            'flag' => $review->forceFill(['is_flagged' => true, 'is_approved' => false])->save(),
            'unflag' => $review->forceFill(['is_flagged' => false])->save(),
            'delete' => $review->delete(),
        };

        $review->movie?->refreshRating();
        AuditLog::record('admin.review.'.$data['action'], $data['action'] === 'delete' ? null : $review, ['review_id' => $review->id], $request, 'admin', $admin->id);

        return back()->with('status', 'Review #'.$review->id.' '.['approve' => 'approved', 'hide' => 'hidden', 'flag' => 'flagged', 'unflag' => 'unflagged', 'delete' => 'deleted'][$data['action']].'.');
    }

    public function settings(Request $request): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            return redirect()->route('admin.login');
        }

        $saved = SiteSetting::query()->pluck('value', 'key');
        $values = collect(SiteSetting::BRAND_DEFAULTS)->map(fn ($default, $key) => (string) ($saved[$key] ?? $default ?? ''))->all();
        $logo = $saved['logo_path'] ?? null;

        return $this->page('Admin/Settings', [
            'values' => [
                ...$values,
                'footer_description' => (string) ($saved['footer_description'] ?? ''),
                'copyright_note' => (string) ($saved['copyright_note'] ?? 'By Syed Ahmer Shah · MIT licence'),
                'default_meta_title' => (string) ($saved['default_meta_title'] ?? ''),
                'default_meta_description' => (string) ($saved['default_meta_description'] ?? ''),
            ],
            'logoUrl' => $logo ? asset('storage/'.$logo) : asset('images/logo-sm.webp'),
            'customLogo' => (bool) $logo,
        ], ['title' => 'Site & brand | Admin', 'description' => 'Brand, contact and social settings.', 'robots' => 'noindex, nofollow']);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $url = ['nullable', 'url:https', 'max:200'];
        $data = $request->validate([
            'site_name' => ['required', 'string', 'min:2', 'max:40'],
            'site_tagline' => ['nullable', 'string', 'max:80'],
            'support_email' => ['required', 'email:rfc', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'contact_address' => ['nullable', 'string', 'max:160'],
            'footer_description' => ['nullable', 'string', 'max:200'],
            'copyright_note' => ['nullable', 'string', 'max:80'],
            'default_meta_title' => ['nullable', 'string', 'max:60'],
            'default_meta_description' => ['nullable', 'string', 'max:160'],
            'social_website' => $url, 'social_github' => $url, 'social_linkedin' => $url, 'social_instagram' => $url,
            'social_facebook' => $url, 'social_x' => $url, 'social_youtube' => $url,
            // Raster only: an uploaded SVG could carry script.
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:min_width=120,min_height=60,max_width=2400,max_height=2400'],
            'reset_logo' => ['nullable', 'boolean'],
        ]);

        foreach (collect($data)->except(['logo', 'reset_logo']) as $key => $value) {
            SiteSetting::put($key, $value);
        }

        $old = SiteSetting::query()->where('key', 'logo_path')->value('value');
        if ($request->hasFile('logo')) {
            SiteSetting::put('logo_path', $request->file('logo')->store('brand', 'public'));
        } elseif ($request->boolean('reset_logo')) {
            SiteSetting::put('logo_path', null);
        }
        if ($old && ($request->hasFile('logo') || $request->boolean('reset_logo'))) {
            Storage::disk('public')->delete($old);
        }

        Cache::forget('site_settings.public');
        LayoutData::flush();
        AuditLog::record('admin.settings.brand', null, collect($data)->except(['logo'])->all(), $request, 'admin', $admin->id);

        return back()->with('status', 'Site details saved. They are live now.');
    }

    /** @return array<string, mixed> */
    private function userRow(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $user->profile_picture ? asset('storage/'.$user->profile_picture) : null,
            'verified' => $user->email_verified_at !== null,
            'banned' => (bool) $user->is_blocked,
            'last_ip' => $user->last_login_ip,
            'last_login' => $user->last_login_at?->diffForHumans(),
            'joined' => $user->created_at?->format('j M Y'),
            'bookings' => (int) ($user->bookings_count ?? 0),
            'reviews' => (int) ($user->reviews_count ?? 0),
        ];
    }

    private function storeIpBan(string $ip, string $reason, Admin $admin, ?int $userId, ?int $days): void
    {
        BannedIp::query()->updateOrCreate(['ip_address' => $ip], [
            'reason' => $reason,
            'banned_by' => $admin->id,
            'user_id' => $userId,
            'expires_at' => $days ? now()->addDays($days) : null,
        ]);
    }
}
