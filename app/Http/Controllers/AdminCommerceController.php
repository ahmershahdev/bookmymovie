<?php

namespace App\Http\Controllers;

use App\Models\Concession;
use App\Models\Coupon;
use App\Models\Movie;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Response;

/**
 * Coupons, snack stock and per-film sales: the levers an admin pulls day to
 * day without opening the full catalogue editor.
 *
 * Every counter shown here (coupon uses, stock) is changed at checkout by
 * guarded atomic UPDATEs, so editing a limit here while customers are
 * checking out can never oversell; it only moves the ceiling.
 */
class AdminCommerceController extends AdminController
{
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->admin($request)) {
            return redirect()->route('admin.login');
        }

        $usage = DB::table('coupon_usages')
            ->selectRaw('coupon_id, COUNT(*) AS uses, COALESCE(SUM(discount_applied), 0) AS given')
            ->groupBy('coupon_id')
            ->get()
            ->keyBy('coupon_id');

        $sold = DB::table('booking_concessions')
            ->join('bookings', 'bookings.id', '=', 'booking_concessions.booking_id')
            ->where('bookings.booking_status', '<>', 'cancelled')
            ->where('bookings.booked_at', '>=', now()->subDays(7))
            ->selectRaw('concession_id, SUM(quantity) AS units')
            ->groupBy('concession_id')
            ->pluck('units', 'concession_id');

        return $this->page('Admin/Commerce', [
            'coupons' => Coupon::query()->orderByDesc('is_active')->orderByDesc('created_at')->get()->map(fn (Coupon $coupon) => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'description' => $coupon->description,
                'type' => $coupon->discount_type,
                'value' => (float) $coupon->discount_value,
                'cap' => $coupon->max_discount_amount !== null ? (float) $coupon->max_discount_amount : null,
                'min_order' => (float) $coupon->min_order_amount,
                'used' => (int) $coupon->used_count,
                'max_uses' => $coupon->max_uses,
                'per_user' => (int) $coupon->max_uses_per_user,
                'from' => $coupon->valid_from?->format('Y-m-d\TH:i'),
                'until' => $coupon->valid_until?->format('Y-m-d\TH:i'),
                'active' => (bool) $coupon->is_active,
                'state' => match (true) {
                    ! $coupon->is_active => 'paused',
                    $coupon->valid_until?->isPast() => 'expired',
                    $coupon->valid_from?->isFuture() => 'scheduled',
                    $coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses => 'used up',
                    default => 'live',
                },
                'given' => (float) ($usage[$coupon->id]->given ?? 0),
            ])->values(),
            'snacks' => Concession::query()->orderBy('sort_order')->get()->map(fn (Concession $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'icon' => $item->icon,
                'price' => (float) $item->price,
                'stock' => $item->stock,
                'low_at' => (int) $item->low_stock_at,
                'active' => (bool) $item->is_active,
                'sold_7d' => (int) ($sold[$item->id] ?? 0),
            ])->values(),
            'films' => Movie::query()->orderByRaw("FIELD(status, 'now_showing', 'coming_soon', 'ended')")->orderBy('title')
                ->get(['id', 'title', 'slug', 'status', 'bookings_enabled', 'hero_carousel_enabled'])
                ->map(fn (Movie $movie) => [
                    'id' => $movie->id,
                    'title' => $movie->title,
                    'slug' => $movie->slug,
                    'status' => $movie->status,
                    'bookings' => (bool) $movie->bookings_enabled,
                    'carousel' => (bool) $movie->hero_carousel_enabled,
                ])->values(),
        ], ['title' => 'Commerce | Admin', 'description' => 'Coupons, snack stock and film sales.', 'robots' => 'noindex, nofollow']);
    }

    public function storeCoupon(Request $request): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate($this->couponRules(), $this->couponMessages());

        $coupon = Coupon::create([...$this->couponAttributes($data), 'code' => $data['code'], 'used_count' => 0, 'created_by' => $admin->id]);
        AuditLog::record('admin.coupon.created', $coupon, ['code' => $coupon->code], $request, 'admin', $admin->id);

        return back()->with('status', 'Coupon '.$coupon->code.' created.');
    }

    public function updateCoupon(Request $request, Coupon $coupon): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $data = $request->validate(collect($this->couponRules())->except('code')->all(), $this->couponMessages());

        if (isset($data['max_uses']) && $data['max_uses'] !== null && (int) $data['max_uses'] < $coupon->used_count) {
            return back()->withErrors(['max_uses' => 'It has already been used '.$coupon->used_count.' times; the limit cannot go below that.']);
        }

        $coupon->fill($this->couponAttributes($data))->save();
        Cache::forget('coupon.exhausted.'.$coupon->code);
        AuditLog::record('admin.coupon.updated', $coupon, ['changes' => array_keys($coupon->getChanges())], $request, 'admin', $admin->id);

        return back()->with('status', 'Coupon '.$coupon->code.' saved.');
    }

    public function toggleCoupon(Request $request, Coupon $coupon): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $coupon->forceFill(['is_active' => ! $coupon->is_active])->save();
        Cache::forget('coupon.exhausted.'.$coupon->code);
        AuditLog::record('admin.coupon.'.($coupon->is_active ? 'resumed' : 'paused'), $coupon, [], $request, 'admin', $admin->id);

        return back()->with('status', $coupon->code.($coupon->is_active ? ' is live again.' : ' is paused.'));
    }

    public function destroyCoupon(Request $request, Coupon $coupon): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);

        // Used coupons are part of booking history; they can only be paused.
        if ($coupon->used_count > 0 || DB::table('coupon_usages')->where('coupon_id', $coupon->id)->exists()) {
            $coupon->forceFill(['is_active' => false])->save();

            return back()->with('status', $coupon->code.' has been used, so it was paused rather than deleted.');
        }

        AuditLog::record('admin.coupon.deleted', null, ['code' => $coupon->code], $request, 'admin', $admin->id);
        $coupon->delete();

        return back()->with('status', 'Coupon deleted.');
    }

    public function updateSnack(Request $request, Concession $concession): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'restock' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'low_at' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($concession, $data) {
            $row = Concession::query()->whereKey($concession->id)->lockForUpdate()->firstOrFail();
            $row->fill([
                'price' => $data['price'],
                'low_stock_at' => $data['low_at'] ?? $row->low_stock_at,
                'is_active' => $data['active'],
            ]);

            // "Add N" is relative, so it never overwrites sales made while
            // the form was open; an absolute count replaces the number.
            if (! empty($data['restock'])) {
                $row->stock = ($row->stock ?? 0) + (int) $data['restock'];
            } elseif (array_key_exists('stock', $data)) {
                $row->stock = $data['stock'];
            }

            $row->save();
        });

        AuditLog::record('admin.snack.updated', $concession->fresh(), collect($data)->all(), $request, 'admin', $admin->id);

        return back()->with('status', $concession->name.' updated.');
    }

    public function updateFilm(Request $request, Movie $movie): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['now_showing', 'coming_soon', 'ended'])],
            'bookings' => ['nullable', 'boolean'],
            'carousel' => ['nullable', 'boolean'],
        ]);

        $movie->forceFill(array_filter([
            'status' => $data['status'] ?? null,
            'bookings_enabled' => $data['bookings'] ?? null,
            'hero_carousel_enabled' => $data['carousel'] ?? null,
        ], fn ($value) => $value !== null))->save();

        Cache::forget('home.rails');
        Cache::forget('nav.movies.cards.v2');
        AuditLog::record('admin.film.access', $movie, $movie->getChanges(), $request, 'admin', $admin->id);

        return back()->with('status', $movie->title.' updated.');
    }

    /** @return array<string, mixed> */
    private function couponRules(): array
    {
        return [
            'code' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Z0-9_-]+$/', 'unique:coupons,code'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:1', 'max:100000'],
            'cap' => ['nullable', 'numeric', 'min:1', 'max:100000'],
            'min_order' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'per_user' => ['required', 'integer', 'min:1', 'max:100'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after:from'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    private function couponMessages(): array
    {
        return [
            'code.regex' => 'Use capital letters, numbers, dashes and underscores only.',
            'code.unique' => 'That code already exists.',
            'until.after' => 'The end must be after the start.',
        ];
    }

    /** @return array<string, mixed> */
    private function couponAttributes(array $data): array
    {
        if ($data['type'] === 'percentage' && (float) $data['value'] > 100) {
            abort(back()->withErrors(['value' => 'A percentage discount cannot exceed 100.']));
        }

        return [
            'description' => $data['description'] ?? null,
            'discount_type' => $data['type'],
            'discount_value' => $data['value'],
            'max_discount_amount' => $data['cap'] ?? null,
            'min_order_amount' => $data['min_order'] ?? 0,
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_user' => $data['per_user'],
            'valid_from' => $data['from'],
            'valid_until' => $data['until'],
            'is_active' => (bool) ($data['active'] ?? true),
        ];
    }
}
