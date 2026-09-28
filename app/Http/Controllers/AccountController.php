<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingEvent;
use App\Models\BookingSeat;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Movie;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\Show;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Concession;
use App\Models\GiftCard;
use App\Models\LoyaltyTransaction;
use App\Payments\PaymentGateway;
use App\Support\BookingLifecycle;
use App\Support\AuditLog;
use App\Support\CleanText;
use App\Support\FormSecurity;
use App\Support\TransactionalMailer;
use App\Support\WalletPass;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

/**
 * Customer account, cart and booking lifecycle.
 *
 * Concurrency model (see SECURITY.md → "Race conditions"):
 *  - Every mutation runs in a transaction retried up to 3 times on deadlock.
 *  - Locks are always taken in the same order: user → show → seats → cart
 *    → coupon, so two requests can never wait on each other in a cycle.
 *  - The database is the final referee: UNIQUE(show_id, seat_id, seat_lock)
 *    on booking_seats and UNIQUE(show_id, seat_id) on cart_items make a
 *    double sale impossible even if application checks were bypassed.
 *  - Counters (show capacity, coupon usage) change through guarded atomic
 *    UPDATE ... WHERE statements rather than read-modify-write.
 */
class AccountController extends Controller
{
    public function dashboard(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $cart = $this->activeCart();

        $upcoming = Booking::query()
            ->with(['show.movie.genres', 'show.screen.theater.city', 'seats.seat'])
            ->where('user_id', $user->id)
            ->where('booking_status', 'confirmed')
            ->whereHas('show', fn ($query) => $query->whereDate('show_date', '>=', now()->toDateString()))
            ->get()
            ->sortBy(fn (Booking $booking) => $booking->showStartsAt())
            ->values();

        $recommended = Movie::query()
            ->withCardMetrics()
            ->with('genres')
            ->where('status', 'now_showing')
            ->whereNotIn('id', $user->wishlists()->select('movie_id'))
            ->orderByDesc('average_rating')
            ->limit(4)
            ->get()
            ->map(fn (Movie $movie) => $movie->toCardArray());

        $hour = now()->hour;

        return $this->page('Account/Dashboard', [
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'),
            'memberSince' => $user->created_at?->format('F Y'),
            'stats' => [
                'bookings' => $user->bookings()->count(),
                'upcoming' => $upcoming->count(),
                'spent' => (float) $user->bookings()->where('booking_status', '<>', 'cancelled')->sum('total_amount'),
                'tickets' => (int) $user->bookings()->where('booking_status', '<>', 'cancelled')->sum('seat_count'),
                'wishlist' => $user->wishlists()->count(),
                'cart' => $cart?->items()->count() ?? 0,
                'reviews' => $user->reviews()->count(),
            ],
            'loyalty' => [
                'points' => (int) $user->loyalty_points,
                'history' => LoyaltyTransaction::query()->where('user_id', $user->id)->latest('created_at')->limit(6)->get()
                    ->map(fn (LoyaltyTransaction $entry) => ['id' => $entry->id, 'points' => (int) $entry->points, 'reason' => $entry->reason, 'ago' => $entry->created_at?->diffForHumans()])->values(),
            ],
            'next' => ($next = $upcoming->first()) ? $this->bookingSummary($next) : null,
            'upcoming' => $upcoming->skip(1)->take(3)->map(fn (Booking $booking) => $this->bookingSummary($booking))->values(),
            'cartExpiresAt' => $cart?->expires_at?->toIso8601String(),
            'recommended' => $recommended->values(),
            'notifications' => Notification::query()->where('user_id', $user->id)->latest('created_at')->limit(5)->get()
                ->map(fn (Notification $note) => [
                    'id' => $note->id,
                    'title' => $note->title,
                    'message' => $note->message,
                    'read' => (bool) $note->is_read,
                    'ago' => $note->created_at?->diffForHumans(),
                ])->values(),
        ], ['title' => 'Your account | BookMyMovie', 'description' => 'Your upcoming shows, bookings, watchlist and account settings.']);
    }

    public function bookings(): Response
    {
        $bookings = Booking::query()
            ->with(['show.movie.genres', 'show.screen.theater.city', 'seats.seat'])
            ->where('user_id', Auth::id())
            ->latest('booked_at')
            ->limit(200)
            ->get();

        return $this->page('Account/Bookings', [
            'bookings' => $bookings->map(function (Booking $booking) {
                $startsAt = $booking->showStartsAt();

                return [
                    ...$this->bookingSummary($booking),
                    'group' => match (true) {
                        $booking->booking_status === 'cancelled' => 'cancelled',
                        $booking->booking_status === 'completed' || ($startsAt && $startsAt->isPast()) => 'past',
                        default => 'upcoming',
                    },
                ];
            })->values(),
        ], ['title' => 'Your bookings | BookMyMovie', 'description' => 'Every booking, e-ticket and receipt in one place.']);
    }

    public function bookingShow(string $number): Response
    {
        $booking = $this->userBooking($number);

        $startsAt = $booking->showStartsAt();
        $theater = $booking->show->screen->theater;

        return $this->page('Account/BookingShow', [
            'booking' => [
                ...$this->bookingSummary($booking),
                'certificate' => $booking->show->movie->certificate_rating,
                'format' => $booking->show->screen->formatLabel(),
                'screen' => $booking->show->screen->screen_name,
                'address' => $theater->address,
                'date' => $startsAt?->format('D j M'),
                'time' => $startsAt?->format('g:i A'),
                'doors' => $startsAt?->copy()->subMinutes(20)->format('g:i A'),
                'tickets' => $booking->seats
                    ->sortBy(fn ($seat) => $seat->seat->row_label.str_pad((string) $seat->seat->seat_number, 3, '0', STR_PAD_LEFT))
                    ->map(fn ($seat) => [
                        'seat' => $seat->seat->row_label.$seat->seat->seat_number,
                        'type' => $seat->ticket_type === 'kid' ? 'Child' : 'Adult',
                        'price' => (float) $seat->price_paid,
                        'code' => $seat->ticket_number,
                    ])->values(),
                'adults' => (int) $booking->adult_count,
                'kids' => (int) $booking->kids_count,
                'subtotal' => (float) $booking->subtotal,
                'discount' => (float) $booking->discount_amount,
                'coupon' => $booking->coupon?->code,
                'cancelled_at' => $booking->cancelled_at?->format('j M Y, g:i A'),
                'paid_at' => $booking->payment?->paid_at?->format('j M, g:i A'),
                'customer' => [
                    'name' => $booking->customer_name ?: $booking->user->name,
                    'email' => $booking->customer_email,
                    'phone' => $booking->customer_phone,
                ],
                'booked_at' => $booking->booked_at?->format('j M Y, g:i A'),
                'map_url' => 'https://www.google.com/maps/search/?api=1&query='.urlencode($theater->name.', '.$theater->address),
                'cancellable' => $booking->isCancellableByCustomer(),
                'addons' => $booking->concessions->map(fn ($item) => ['name' => $item->name, 'name_ur' => $item->name_ur, 'quantity' => (int) $item->pivot->quantity, 'total' => (float) $item->pivot->unit_price * $item->pivot->quantity])->values(),
                'addons_total' => (float) $booking->addons_total,
                'gift_card_amount' => (float) $booking->gift_card_amount,
                'points_redeemed' => (int) $booking->points_redeemed,
                'points_earned' => (int) $booking->points_earned,
                'qr' => BookingLifecycle::ticketPayload($booking->booking_number),
                'wallet' => [
                    'apple' => WalletPass::appleEnabled() ? route('tickets.wallet.apple', $booking->booking_number) : null,
                    'google' => WalletPass::googleEnabled() ? route('tickets.wallet.google', $booking->booking_number) : null,
                ],
                'cancel_until' => $startsAt?->copy()->subMinutes((int) config('bookmymovie.booking.cancellation_cutoff_minutes', 120))->format('D j M, g:i A'),
            ],
        ], ['title' => 'E-ticket '.$booking->booking_number, 'description' => 'Your BookMyMovie e-ticket.'], [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Account', 'url' => route('user.dashboard')],
            ['label' => 'Bookings', 'url' => route('user.bookings')],
            ['label' => $booking->booking_number, 'url' => null],
        ]);
    }

    public function tracking(string $number): Response
    {
        $booking = $this->userBooking($number);

        $startsAt = $booking->showStartsAt();
        $cancelled = $booking->booking_status === 'cancelled';
        $doors = $startsAt->copy()->subMinutes(20);

        return $this->page('Account/Tracking', [
            'booking' => [
                ...$this->bookingSummary($booking),
                'when' => $startsAt->format('l j F, g:i A'),
                'cancelled_at' => $booking->cancelled_at?->format('j M Y, g:i A'),
                'cancelled_by' => $booking->cancelled_by,
                'cancellation_reason' => $booking->cancellation_reason,
            ],
            'milestones' => [
                ['title' => 'Booked', 'copy' => 'Seats locked and booking confirmed.', 'done' => true, 'at' => $booking->booked_at?->format('D j M, g:i A')],
                ['title' => 'Paid', 'copy' => 'Payment received at the box office.', 'done' => $booking->payment_status === 'paid', 'at' => $booking->payment?->paid_at?->format('D j M, g:i A')],
                ['title' => 'Doors open', 'copy' => 'Head to '.$booking->show->screen->screen_name.'.', 'done' => $doors->isPast() && ! $cancelled, 'at' => $doors->format('D j M, g:i A')],
                ['title' => 'Showtime', 'copy' => 'Enjoy the film.', 'done' => ($startsAt->isPast() || $booking->booking_status === 'completed') && ! $cancelled, 'at' => $startsAt->format('D j M, g:i A')],
            ],
            'events' => $booking->events->map(fn ($event) => [
                'id' => $event->id,
                'at' => $event->created_at?->format('D j M Y, g:i A'),
                'actor' => $event->actor_type,
                'event' => Str::headline($event->event),
                'cancelled' => $event->event === 'cancelled',
                'note' => $event->note,
            ])->values(),
        ], ['title' => 'Track '.$booking->booking_number, 'description' => 'Booking status and history.'], [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Account', 'url' => route('user.dashboard')],
            ['label' => 'Bookings', 'url' => route('user.bookings')],
            ['label' => $booking->booking_number, 'url' => route('user.booking.show', $booking->booking_number)],
            ['label' => 'Track', 'url' => null],
        ]);
    }

    /**
     * Customer cancellation of an unpaid booking before the cutoff. Releases
     * the seats, restores show capacity and returns any coupon use.
     */
    public function cancelBooking(Request $request, string $number): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        $result = DB::transaction(function () use ($number, $data) {
            $booking = Booking::query()
                ->where('user_id', Auth::id())
                ->where('booking_number', $number)
                ->lockForUpdate()
                ->firstOrFail();

            $booking->load('show');

            if (! $booking->isCancellableByCustomer()) {
                return 'not-cancellable';
            }

            BookingLifecycle::unwind($booking, 'user', Auth::id(), $data['reason'] ?: 'Cancelled by customer');

            return $booking;
        }, 3);

        if ($result === 'not-cancellable') {
            return back()->withErrors(['booking' => 'This booking can no longer be cancelled online. Paid bookings and shows starting within two hours are handled at the box office.']);
        }

        AuditLog::record('booking.cancelled', $result, ['reason' => $data['reason'] ?? null]);
        TransactionalMailer::bookingCancelled(Auth::user(), $result);

        return redirect()->route('user.booking.show', $number)->with('status', 'Booking cancelled. Your seats have been released.');
    }

    public function wishlist(): Response
    {
        $wishlistMovies = Movie::query()
            ->withCardMetrics()
            ->with('genres')
            ->join('wishlists', 'wishlists.movie_id', '=', 'movies.id')
            ->where('wishlists.user_id', Auth::id())
            ->orderByDesc('wishlists.created_at')
            ->get()
            ->map(fn (Movie $movie) => $movie->toCardArray());

        return $this->page('Account/Wishlist', [
            'movies' => $wishlistMovies->values(),
        ], ['title' => 'Your watchlist | BookMyMovie', 'description' => 'Films you saved to watch.']);
    }

    public function addWishlist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'movie_id' => ['nullable', 'integer', 'required_without:slug'],
            'slug' => ['nullable', 'string', 'max:230'],
        ]);

        $movie = isset($data['movie_id'])
            ? Movie::query()->findOrFail($data['movie_id'])
            : Movie::query()->where('slug', $data['slug'] ?? '')->firstOrFail();

        // INSERT IGNORE against UNIQUE(user_id, movie_id): safe under double clicks.
        Wishlist::query()->insertOrIgnore(['user_id' => Auth::id(), 'movie_id' => $movie->id, 'created_at' => now()]);

        return back()->with('status', '“'.$movie->title.'” saved to your watchlist.');
    }

    public function removeWishlist(Movie $movie): RedirectResponse
    {
        Wishlist::query()->where('user_id', Auth::id())->where('movie_id', $movie->id)->delete();

        return back()->with('status', '“'.$movie->title.'” removed from your watchlist.');
    }

    public function cart(): Response
    {
        $cart = $this->activeCart();
        $items = $this->cartItems($cart);

        return $this->page('Account/Cart', [
            ...$this->cartPayload($cart, $items),
            'maxSeats' => $this->maxSeats(),
        ], ['title' => 'Your cart | BookMyMovie', 'description' => 'Seats on hold for your booking.']);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $maxSeats = $this->maxSeats();

        $data = $request->validate([
            'show_id' => ['required', 'integer'],
            'seats' => ['required', 'array', 'min:1', 'max:'.$maxSeats],
            'seats.*' => ['integer', 'distinct'],
            'ticket_type' => ['nullable', Rule::in(['adult', 'kid'])],
        ], [
            'seats.required' => 'Select at least one seat on the map.',
            'seats.max' => 'You can hold up to '.$maxSeats.' seats per booking.',
        ]);

        $seatIds = array_values(array_unique(array_map('intval', $data['seats'])));
        $ticketType = $data['ticket_type'] ?? 'adult';

        $this->clearExpiredCarts();

        try {
            DB::transaction(function () use ($seatIds, $data, $ticketType, $maxSeats) {
                // Serialises this user's cart operations (double clicks, two tabs).
                User::query()->whereKey(Auth::id())->lockForUpdate()->firstOrFail();

                $show = Show::query()->lockForUpdate()->find($data['show_id']);

                if (! $show || ! $this->isOnSale($show)) {
                    throw new \RuntimeException('show-unavailable');
                }

                $seatCount = Seat::query()
                    ->where('screen_id', $show->screen_id)
                    ->where('is_active', true)
                    ->whereIn('id', $seatIds)
                    ->lockForUpdate()
                    ->count();

                if ($seatCount !== count($seatIds)) {
                    throw new \RuntimeException('seat-conflict');
                }

                $cart = Cart::query()->where('user_id', Auth::id())->lockForUpdate()->first()
                    ?? Cart::create(['user_id' => Auth::id(), 'expires_at' => now()]);

                // One show per cart keeps checkout, pricing and tickets simple.
                if ($cart->items()->where('show_id', '<>', $show->id)->exists()) {
                    throw new \RuntimeException('mixed-show');
                }

                $alreadyHeld = $cart->items()->where('show_id', $show->id)->whereIn('seat_id', $seatIds)->pluck('seat_id')->all();
                $seatIds = array_values(array_diff($seatIds, $alreadyHeld));

                if ($seatIds === []) {
                    throw new \RuntimeException('cart-unchanged');
                }

                if ($cart->items()->count() + count($seatIds) > $maxSeats) {
                    throw new \RuntimeException('cart-limit');
                }

                $available = DB::table('v_seat_availability')
                    ->where('show_id', $show->id)
                    ->whereIn('seat_id', $seatIds)
                    ->where('seat_status', 'available')
                    ->get()
                    ->keyBy('seat_id');

                if ($available->count() !== count($seatIds)) {
                    throw new \RuntimeException('seat-conflict');
                }

                // Rows left behind by other users' expired carts would trip the
                // unique key; they no longer hold anything, so clear them.
                CartItem::query()
                    ->where('show_id', $show->id)
                    ->whereIn('seat_id', $seatIds)
                    ->whereHas('cart', fn ($query) => $query->where('expires_at', '<=', now()))
                    ->delete();

                CartItem::insert(array_map(function (int $seatId) use ($available, $cart, $show, $ticketType) {
                    $seat = $available[$seatId];
                    [$type, $price] = $this->seatPrice($seat, $ticketType);

                    return [
                        'cart_id' => $cart->id,
                        'show_id' => $show->id,
                        'seat_id' => $seatId,
                        'seat_category_id' => $seat->seat_category_id,
                        'ticket_type' => $type,
                        'price' => $price,
                        'added_at' => now(),
                    ];
                }, $seatIds));

                $cart->forceFill(['expires_at' => now()->addMinutes($this->holdMinutes())])->save();
            }, 3);
        } catch (QueryException) {
            // UNIQUE(show_id, seat_id) on cart_items: someone else won the race.
            return back()->withErrors(['seats' => 'Someone reserved one of those seats a moment ago. Please pick different seats.']);
        } catch (\RuntimeException $exception) {
            return match ($exception->getMessage()) {
                'cart-limit' => back()->withErrors(['seats' => 'You can hold up to '.$maxSeats.' seats per booking. Remove a seat from your cart first.']),
                'cart-unchanged' => redirect()->route('user.cart')->with('status', 'Those seats are already in your cart.'),
                'show-unavailable' => back()->withErrors(['show_id' => 'This show has started or is no longer on sale.']),
                'mixed-show' => back()->withErrors(['seats' => 'Your cart already holds seats for a different show. Check out or clear it first.']),
                'seat-conflict' => back()->withErrors(['seats' => 'One or more of those seats has just been taken. The map has been refreshed.']),
                default => throw $exception,
            };
        }

        return redirect()->route('user.cart')->with('status', count($seatIds).' '.Str::plural('seat', count($seatIds)).' held for '.$this->holdMinutes().' minutes.');
    }

    public function removeCartItem(CartItem $item): RedirectResponse
    {
        DB::transaction(function () use ($item) {
            $cart = Cart::query()->where('user_id', Auth::id())->lockForUpdate()->first();

            abort_unless($cart && $item->cart_id === $cart->id, 404);

            $item->delete();

            if (! $cart->items()->exists()) {
                $cart->delete();
            }
        });

        return back()->with('status', 'Seat released.');
    }

    public function clearCart(): RedirectResponse
    {
        Cart::query()->where('user_id', Auth::id())->delete();

        return redirect()->route('user.cart')->with('status', 'Cart cleared and seats released.');
    }

    public function checkout(): Response|RedirectResponse
    {
        $cart = $this->activeCart();
        $items = $this->cartItems($cart);

        if ($items->isEmpty()) {
            return redirect()->route('user.cart')->withErrors(['cart' => 'Your cart is empty or your hold has expired.']);
        }

        /** @var User $user */
        $user = Auth::user();

        return $this->page('Account/Checkout', [
            ...$this->cartPayload($cart, $items),
            // A fresh key per checkout page view; a resubmission reuses it.
            'idempotencyKey' => (string) Str::uuid(),
            'captcha' => FormSecurity::forPage('checkout'),
            'paymentMethods' => PaymentGateway::available(),
            'concessions' => Concession::query()->where('is_active', true)->where(fn ($query) => $query->whereNull('stock')->orWhere('stock', '>', 0))->orderBy('sort_order')->get()
                ->map(fn (Concession $item) => ['id' => $item->id, 'name' => $item->name, 'name_ur' => $item->name_ur, 'description' => $item->description, 'category' => $item->category, 'price' => (float) $item->price, 'icon' => $item->icon, 'left' => $item->stock !== null && $item->stock <= $item->low_stock_at ? $item->stock : null])->values(),
            'points' => (int) $user->loyalty_points,
            'customer' => ['name' => $user->name, 'email' => $user->email, 'phone' => (string) $user->phone, 'address' => (string) $user->address],
        ], ['title' => 'Checkout | BookMyMovie', 'description' => 'Confirm your booking.']);
    }

    public function placeBooking(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'checkout');

        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'coupon_code' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]*$/'],
            'terms' => ['accepted'],
            'payment_method' => ['nullable', Rule::in(array_column(PaymentGateway::available(), 'key'))],
            'addons' => ['nullable', 'array', 'max:20'],
            'addons.*' => ['integer', 'min:0', 'max:10'],
            'gift_card_code' => ['nullable', 'string', 'max:24', 'regex:/^[A-Za-z0-9-]*$/'],
            'use_points' => ['nullable', 'boolean'],
        ], [
            'terms.accepted' => 'Please confirm you have read the cancellation policy.',
            'payment_method.in' => 'Choose one of the payment options shown.',
        ]);
        $data['payment_method'] ??= 'cod';

        // A retried or double-clicked submit returns the booking it already made.
        if ($existing = Booking::query()->where('idempotency_key', $data['idempotency_key'])->where('user_id', Auth::id())->first()) {
            return redirect()->route('user.booking.show', $existing->booking_number);
        }

        /** @var User $user */
        $user = Auth::user();
        $maxSeats = $this->maxSeats();

        try {
            $booking = DB::transaction(function () use ($user, $data, $maxSeats) {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                // Same lock order as addToCart: user → show → cart.
                $showId = CartItem::query()
                    ->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))
                    ->value('show_id');
                $show = $showId ? Show::query()->lockForUpdate()->find($showId) : null;

                $cart = Cart::query()
                    ->where('user_id', $user->id)
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                $items = $cart ? $cart->items()->with('seat')->lockForUpdate()->get() : collect();

                if (! $show || $items->isEmpty()) {
                    throw new \RuntimeException('cart-empty');
                }

                if ($items->count() > $maxSeats) {
                    throw new \RuntimeException('cart-limit');
                }

                if ($items->pluck('show_id')->unique()->all() !== [$show->id]) {
                    throw new \RuntimeException('mixed-show');
                }

                if (! $this->isOnSale($show)) {
                    throw new \RuntimeException('show-unavailable');
                }

                $taken = BookingSeat::query()
                    ->where('show_id', $show->id)
                    ->whereIn('seat_id', $items->pluck('seat_id'))
                    ->where('seat_lock', 1)
                    ->exists();

                if ($taken) {
                    throw new \RuntimeException('seat-conflict');
                }

                $subtotal = round((float) $items->sum('price'), 2);
                $coupon = $this->lockedCoupon($data['coupon_code'] ?? null, $subtotal, $user->id);
                $discount = $coupon ? $this->discountAmount($coupon, $subtotal) : 0.0;

                // Food and drink: prices come from the database, never the form.
                $addons = collect($data['addons'] ?? [])->map(fn ($quantity) => (int) $quantity)->filter(fn ($quantity) => $quantity > 0);
                $concessions = $addons->isEmpty() ? collect() : Concession::query()->where('is_active', true)->whereIn('id', $addons->keys())->get()->keyBy('id');
                $addonsTotal = round($concessions->sum(fn (Concession $item) => (float) $item->price * $addons[$item->id]), 2);

                $due = round($subtotal - $discount + $addonsTotal, 2);

                // Gift card balance, locked so two checkouts cannot spend it twice.
                $giftCard = null;
                $giftAmount = 0.0;
                if (filled($data['gift_card_code'] ?? null)) {
                    $giftCard = GiftCard::query()->where('code', strtoupper(trim($data['gift_card_code'])))->lockForUpdate()->first();
                    if (! $giftCard || ! $giftCard->usable()) {
                        throw new \RuntimeException('gift-card-invalid');
                    }
                    $giftAmount = round(min((float) $giftCard->balance, $due), 2);
                    $due = round($due - $giftAmount, 2);
                }

                // Loyalty points: 1 point = PKR 1, up to what is still due.
                $pointsUsed = 0;
                if (! empty($data['use_points'])) {
                    $pointsUsed = (int) min((int) User::query()->whereKey($user->id)->value('loyalty_points'), floor($due / BookingLifecycle::POINT_VALUE));
                    $due = round($due - $pointsUsed * BookingLifecycle::POINT_VALUE, 2);
                }

                $total = max(0, $due);
                $method = $total <= 0 ? 'cod' : $data['payment_method'];

                $booking = Booking::create([
                    'booking_number' => $this->bookingNumber(),
                    'idempotency_key' => $data['idempotency_key'],
                    'user_id' => $user->id,
                    'customer_name' => $data['name'],
                    'customer_email' => strtolower($data['email']),
                    'customer_phone' => $data['phone'],
                    'customer_address' => $data['address'] ?? null,
                    'show_id' => $show->id,
                    'coupon_id' => $coupon?->id,
                    'seat_count' => $items->count(),
                    'adult_count' => $items->where('ticket_type', 'adult')->count(),
                    'kids_count' => $items->where('ticket_type', 'kid')->count(),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'addons_total' => $addonsTotal,
                    'gift_card_id' => $giftCard?->id,
                    'gift_card_amount' => $giftAmount,
                    'points_redeemed' => $pointsUsed,
                    'total_amount' => $total,
                    'payment_method' => $method,
                    'payment_status' => $total <= 0 ? 'paid' : 'pending',
                    'booking_status' => 'confirmed',
                ]);

                BookingSeat::insert($items->map(fn (CartItem $item) => [
                    'booking_id' => $booking->id,
                    'seat_id' => $item->seat_id,
                    'show_id' => $item->show_id,
                    'seat_category_id' => $item->seat_category_id,
                    'ticket_type' => $item->ticket_type,
                    'price_paid' => $item->price,
                    'ticket_number' => $booking->booking_number.'-'.$item->seat->row_label.$item->seat->seat_number,
                    'seat_lock' => 1,
                    'created_at' => now(),
                ])->all());

                Payment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $method,
                    'amount' => $total,
                    'status' => $total <= 0 ? 'paid' : 'pending',
                    'paid_at' => $total <= 0 ? now() : null,
                    'notes' => $total <= 0 ? 'Covered in full by gift card and points.' : ($method === 'cod' ? 'Pay at the cinema box office before the show.' : 'Awaiting online payment.'),
                ]);

                foreach ($concessions as $item) {
                    // Tracked stock is taken with a guarded UPDATE: the last
                    // units can only go to one buyer, however many check out at once.
                    if ($item->stock !== null) {
                        $taken = Concession::query()->whereKey($item->id)->where('stock', '>=', $addons[$item->id])->decrement('stock', $addons[$item->id]);
                        if ($taken !== 1) {
                            throw new \RuntimeException('addon-sold-out');
                        }
                    }
                    $booking->concessions()->attach($item->id, ['quantity' => $addons[$item->id], 'unit_price' => $item->price]);
                }

                if ($giftCard && $giftAmount > 0) {
                    // Guarded decrement: never below zero.
                    $spent = GiftCard::query()->whereKey($giftCard->id)->where('balance', '>=', $giftAmount)->decrement('balance', $giftAmount);
                    if ($spent !== 1) {
                        throw new \RuntimeException('gift-card-invalid');
                    }
                }

                if ($pointsUsed > 0) {
                    $spent = User::query()->whereKey($user->id)->where('loyalty_points', '>=', $pointsUsed)->decrement('loyalty_points', $pointsUsed);
                    if ($spent !== 1) {
                        throw new \RuntimeException('points-changed');
                    }
                    LoyaltyTransaction::create(['user_id' => $user->id, 'booking_id' => $booking->id, 'points' => -$pointsUsed, 'reason' => 'Spent on '.$booking->booking_number]);
                }

                BookingLifecycle::awardPoints($booking);

                if ($coupon) {
                    // Guarded increment: fails instead of overshooting max_uses.
                    $claimed = Coupon::query()
                        ->whereKey($coupon->id)
                        ->where(fn ($query) => $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
                        ->increment('used_count');

                    if ($claimed !== 1) {
                        Cache::put('coupon.exhausted.'.$coupon->code, true, now()->addMinutes(2));
                        throw new \RuntimeException('coupon-invalid');
                    }

                    DB::table('coupon_usages')->insert([
                        'coupon_id' => $coupon->id,
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'discount_applied' => $discount,
                        'used_at' => now(),
                    ]);
                }

                $seated = Show::query()
                    ->whereKey($show->id)
                    ->whereRaw('booked_seats + ? <= total_seats', [$items->count()])
                    ->increment('booked_seats', $items->count());

                if ($seated !== 1) {
                    throw new \RuntimeException('seat-conflict');
                }

                BookingEvent::create([
                    'booking_id' => $booking->id,
                    'event' => 'confirmed',
                    'to_status' => 'confirmed',
                    'note' => 'Booking confirmed online. Payment due at the counter.',
                    'actor_type' => 'user',
                    'actor_id' => $user->id,
                ]);

                $user->forceFill([
                    'phone' => $user->phone ?: $data['phone'],
                    'address' => $user->address ?: ($data['address'] ?? null),
                ])->save();

                $cart->delete();

                return $booking;
            }, 3);
        } catch (QueryException $exception) {
            // Duplicate idempotency key: a concurrent twin of this request won.
            if ($existing = Booking::query()->where('idempotency_key', $data['idempotency_key'])->where('user_id', $user->id)->first()) {
                return redirect()->route('user.booking.show', $existing->booking_number);
            }

            // 1205 lock wait timeout / 1213 deadlock: a rush on the same show
            // or coupon, not a lost seat. The whole transaction rolled back.
            if (in_array($exception->errorInfo[1] ?? null, [1205, 1213], true)) {
                return redirect()->route('user.checkout')->withErrors(['cart' => 'It is very busy right now and your booking could not be confirmed in time. Nothing was charged and your seats are still held; please try again.'])->withInput($request->except('custom_captcha_answer'));
            }

            report($exception);

            return redirect()->route('user.cart')->withErrors(['cart' => 'One or more of your seats was just booked by someone else. Please choose again.']);
        } catch (\RuntimeException $exception) {
            $message = match ($exception->getMessage()) {
                'cart-empty' => 'Your seat hold expired before checkout finished. Please select your seats again.',
                'show-unavailable' => 'This show has started or is no longer on sale.',
                'cart-limit' => 'You can book up to '.$maxSeats.' seats at once.',
                'mixed-show' => 'Please check out one show at a time.',
                'coupon-invalid' => 'That coupon is not valid for this booking (expired, used up, or below the minimum spend).',
                'coupon-user-limit' => 'You have already used this coupon the maximum number of times.',
                'gift-card-invalid' => 'That gift card is not valid, has expired or has no balance left.',
                'points-changed' => 'Your points balance changed while you were checking out. Please try again.',
                'addon-sold-out' => 'One of your snacks just sold out. Please adjust your order.',
                'sales-paused' => 'Bookings for this film are paused right now. Please try again later.',
                'busy' => 'It is very busy right now and your seats could not be confirmed in time. Nothing was charged; please try again.',
                default => 'One or more of your seats was just booked by someone else. Please choose again.',
            };

            $field = match ($exception->getMessage()) {
                'coupon-invalid', 'coupon-user-limit' => 'coupon_code',
                'gift-card-invalid' => 'gift_card_code',
                'points-changed' => 'use_points',
                'addon-sold-out' => 'addons',
                default => 'cart',
            };
            $target = $field === 'cart' ? 'user.cart' : 'user.checkout';

            return redirect()->route($target)->withErrors([$field => $message])->withInput($request->except('custom_captcha_answer'));
        }

        $booking->load('user');
        AuditLog::record('booking.created', $booking, ['seats' => $booking->seat_count, 'total' => (float) $booking->total_amount, 'method' => $booking->payment_method]);
        TransactionalMailer::bookingConfirmed($booking->user, $booking);

        if ($booking->payment_method !== 'cod' && $booking->payment_status !== 'paid') {
            return redirect()->route('payments.start', $booking->booking_number);
        }

        return redirect()->route('user.booking.show', $booking->booking_number)->with('status', 'You are booked. See you at the movies.');
    }

    public function profile(): Response
    {
        $user = Auth::user();

        $lastBooking = $user->bookings()->latest('booked_at')->with('show.movie')->first();

        return $this->page('Account/Profile', [
            'profile' => [
                'name' => $user->name,
                'username' => (string) $user->username,
                'bio' => (string) $user->bio,
                'city' => (string) $user->city,
                'email' => $user->email,
                'phone' => (string) $user->phone,
                'address' => (string) $user->address,
                'date_of_birth' => $user->date_of_birth?->toDateString() ?? '',
                'gender' => (string) $user->gender,
                'avatar' => $user->profile_picture ? asset('storage/'.$user->profile_picture) : null,
                'verified' => (bool) $user->email_verified_at,
                'member_since' => $user->created_at?->format('j F Y'),
                'last_film' => $lastBooking?->show?->movie?->title,
                'max_birth_date' => now()->subYears(10)->toDateString(),
                'two_factor_enabled' => (bool) $user->two_factor_enabled,
                'locale' => $user->locale ?: 'en',
            ],
        ], ['title' => 'Profile & security | BookMyMovie', 'description' => 'Manage your details, photo and password.']);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'name' => CleanText::nameRules(),
            'username' => CleanText::usernameRules($user->id),
            'bio' => ['nullable', 'string', 'max:160', CleanText::noProfanity('Your bio')],
            'city' => ['nullable', 'string', 'max:60', 'regex:/^[\pL\pM .\'-]+$/u'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\-\s()]+$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date', 'before:-10 years', 'after:1900-01-01'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'current_password' => ['nullable', 'required_with:password', 'string', 'max:72'],
            'password' => ['nullable', 'string', 'max:72', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=96,min_height=96,max_width=4000,max_height=4000'],
            'two_factor_enabled' => ['nullable', 'boolean'],
        ], [...CleanText::nameMessages(), ...CleanText::usernameMessages(), 'city.regex' => 'Please use letters only for your city.']);

        $emailChanged = strtolower($data['email']) !== $user->email;

        // Changing the email or password requires proof of the current password.
        if (($emailChanged || ! empty($data['password'])) && ! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
            return back()->withErrors(['current_password' => 'Enter your current password to change your email or password.'])->withInput($request->except('current_password', 'password', 'password_confirmation'));
        }

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');

            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $user->profile_picture = $path;
        }

        $user->fill([
            'name' => CleanText::normaliseName($data['name']),
            'username' => $data['username'],
            'bio' => filled($data['bio'] ?? null) ? strip_tags($data['bio']) : null,
            'city' => filled($data['city'] ?? null) ? strip_tags($data['city']) : null,
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? $user->gender,
        ]);
        $user->two_factor_enabled = $request->boolean('two_factor_enabled');

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->setRememberToken(Str::random(60));
        }

        $user->save();

        AuditLog::record('account.profile_updated', $user, ['fields' => array_keys($user->getChanges())]);

        if (! empty($data['password'])) {
            // Keep this device signed in, sign out every other session.
            DB::table('sessions')->where('user_id', $user->id)->where('id', '<>', $request->session()->getId())->delete();
            $request->session()->regenerate();
            AuditLog::record('account.password_changed', $user);
            TransactionalMailer::passwordChanged($user, $request);
        }

        return back()->with('status', 'Profile saved.');
    }

    /**
     * The shape the booking list rows, dashboard and e-ticket share.
     *
     * @return array<string, mixed>
     */
    private function bookingSummary(Booking $booking): array
    {
        $movie = $booking->show->movie;
        $startsAt = $booking->showStartsAt();
        [$state, $tone] = match (true) {
            $booking->booking_status === 'cancelled' => ['Cancelled', 'signal'],
            $booking->booking_status === 'completed' || ($startsAt && $startsAt->isPast()) => ['Watched', ''],
            $booking->payment_status === 'paid' => ['Paid · upcoming', 'mint'],
            default => ['Pay at counter', 'volt'],
        };

        return [
            'number' => $booking->booking_number,
            'state' => $state,
            'tone' => $tone,
            'cancelled' => $booking->booking_status === 'cancelled',
            'paid' => $booking->payment_status === 'paid',
            'method' => $booking->payment_method,
            'method_label' => PaymentGateway::METHODS[$booking->payment_method]['label'] ?? 'Pay at the counter',
            'can_pay_online' => $booking->payment_method !== 'cod' && $booking->payment_status !== 'paid' && $booking->booking_status !== 'cancelled' && PaymentGateway::enabled($booking->payment_method),
            'movie' => $movie->toCardArray(0),
            'title' => $movie->title,
            'starts' => $startsAt?->format('D j M, g:i A'),
            'relative' => $startsAt ? ($startsAt->isToday() ? 'Tonight' : ($startsAt->isTomorrow() ? 'Tomorrow' : $startsAt->format('l'))).' · in '.$startsAt->diffForHumans(null, true, false, 2) : null,
            'theater' => $booking->show->screen->theater->name,
            'seats' => $booking->seats->map(fn ($seat) => $seat->seat->row_label.$seat->seat->seat_number)->values(),
            'seat_count' => (int) $booking->seat_count,
            'total' => (float) $booking->total_amount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cartPayload(?Cart $cart, $items): array
    {
        $first = $items->first();
        $show = $first?->show;
        $startsAt = $show ? Carbon::parse($show->show_date->toDateString().' '.$show->show_time) : null;
        $tiers = ['Gold' => 'Classic', 'Platinum' => 'Prime', 'Box' => 'Recliner'];

        return [
            'expiresAt' => $cart?->expires_at?->toIso8601String(),
            'total' => (float) $items->sum('price'),
            'show' => $show ? [
                'id' => $show->id,
                'movie' => $show->movie->toCardArray(0),
                'title' => $show->movie->title,
                'slug' => $show->movie->slug,
                'when' => $startsAt->format('l j F · g:i A'),
                'short' => $startsAt->format('D j M, g:i A'),
                'theater' => $show->screen->theater->name,
                'screen' => $show->screen->screen_name,
                'format' => $show->screen->formatLabel(),
            ] : null,
            'items' => $items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'seat' => $item->seat->row_label.$item->seat->seat_number,
                'type' => $item->ticket_type === 'kid' ? 'Child' : 'Adult',
                'tier' => $tiers[$item->category->name] ?? $item->category->name,
                'price' => (float) $item->price,
            ])->values(),
        ];
    }

    private function activeCart(): ?Cart
    {
        $this->clearExpiredCarts();

        return Cart::query()
            ->where('user_id', Auth::id())
            ->where('expires_at', '>', now())
            ->first();
    }

    private function cartItems(?Cart $cart)
    {
        return $cart
            ? $cart->items()->with(['show.movie.genres', 'show.screen.theater.city', 'seat', 'category'])->get()->sortBy(fn (CartItem $item) => $item->seat->row_label.str_pad((string) $item->seat->seat_number, 3, '0', STR_PAD_LEFT))->values()
            : collect();
    }

    private function clearExpiredCarts(): void
    {
        Cart::query()->where('expires_at', '<=', now())->delete();
    }

    private function userBooking(string $number): Booking
    {
        return Booking::query()
            ->with(['user', 'show.movie.genres', 'show.screen.theater.city', 'seats.seat', 'seats.category', 'payment', 'coupon', 'events', 'concessions'])
            ->where('user_id', Auth::id())
            ->where('booking_number', $number)
            ->firstOrFail();
    }

    private function isOnSale(Show $show): bool
    {
        $startsAt = Carbon::parse($show->show_date->toDateString().' '.$show->show_time);

        return $show->status === 'scheduled' && $startsAt->isFuture()
            && (bool) Movie::query()->whereKey($show->movie_id)->value('bookings_enabled');
    }

    private function bookingNumber(): string
    {
        do {
            $number = 'BM-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        } while (Booking::query()->where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Child pricing only exists where the cinema set it (recliners are adult
     * only); otherwise the seat is sold as an adult ticket.
     *
     * @return array{0: string, 1: float}
     */
    private function seatPrice(object $seat, string $ticketType): array
    {
        if ($ticketType === 'kid' && ($seat->kids_sale_price !== null || $seat->kids_price !== null)) {
            return ['kid', (float) ($seat->kids_sale_price ?? $seat->kids_price)];
        }

        return ['adult', (float) ($seat->sale_price ?? $seat->price)];
    }

    private function lockedCoupon(?string $code, float $subtotal, int $userId): ?Coupon
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        // Thousands claiming one code: once it is used up, later requests fail
        // here from the cache instead of queueing on the coupon row's lock.
        if (Cache::has('coupon.exhausted.'.$code)) {
            throw new \RuntimeException('coupon-invalid');
        }

        // A plain read: the final say is the guarded UPDATE on used_count at
        // the end of the transaction, so the hot row is locked only briefly.
        $coupon = Coupon::query()->where('code', $code)->first();

        if ($coupon && $coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            Cache::put('coupon.exhausted.'.$code, true, now()->addMinutes(2));
        }

        if (
            ! $coupon
            || ! $coupon->is_active
            || now()->lt($coupon->valid_from)
            || now()->gt($coupon->valid_until)
            || $subtotal < (float) $coupon->min_order_amount
            || ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses)
        ) {
            throw new \RuntimeException('coupon-invalid');
        }

        $userUses = DB::table('coupon_usages')
            ->where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->count();

        if ($userUses >= $coupon->max_uses_per_user) {
            throw new \RuntimeException('coupon-user-limit');
        }

        return $coupon;
    }

    private function discountAmount(Coupon $coupon, float $subtotal): float
    {
        $discount = $coupon->discount_type === 'percentage'
            ? $subtotal * ((float) $coupon->discount_value / 100)
            : (float) $coupon->discount_value;

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return round(min($discount, $subtotal), 2);
    }

    private function maxSeats(): int
    {
        return (int) config('bookmymovie.booking.max_seats_per_booking', 4);
    }

    private function holdMinutes(): int
    {
        return (int) config('bookmymovie.booking.cart_hold_minutes', 10);
    }
}
