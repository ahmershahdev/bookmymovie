<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Show;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        $user = Auth::user();
        $cart = $this->activeCart();

        return view('user.dashboard', [
            'stats' => [
                'bookings' => $user->bookings()->count(),
                'spent' => (float) $user->bookings()->where('booking_status', '<>', 'cancelled')->sum('total_amount'),
                'wishlist' => $user->wishlists()->count(),
                'cart' => $cart?->items()->count() ?? 0,
            ],
        ]);
    }

    public function bookings(): View
    {
        $bookings = Booking::query()
            ->with(['show.movie', 'show.screen.theater'])
            ->where('user_id', Auth::id())
            ->latest('booked_at')
            ->get();

        return view('user.bookings', compact('bookings'));
    }

    public function bookingShow(string $number): View
    {
        $booking = $this->userBooking($number);

        return view('user.booking-show', compact('booking', 'number'));
    }

    public function tracking(string $number): View
    {
        $booking = $this->userBooking($number);

        return view('user.tracking', compact('booking', 'number'));
    }

    public function wishlist(): View
    {
        $wishlistMovies = Wishlist::query()
            ->with('movie.genres')
            ->where('user_id', Auth::id())
            ->latest('created_at')
            ->get()
            ->pluck('movie')
            ->filter()
            ->map(fn (Movie $movie) => $movie->toCardArray());

        return view('user.wishlist', compact('wishlistMovies'));
    }

    public function toggleWishlist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
            'slug' => ['nullable', 'string', 'exists:movies,slug'],
        ]);

        $movie = isset($data['movie_id'])
            ? Movie::findOrFail($data['movie_id'])
            : Movie::where('slug', $data['slug'] ?? '')->firstOrFail();

        $existing = Wishlist::query()->where('user_id', Auth::id())->where('movie_id', $movie->id)->first();

        if ($existing) {
            $existing->delete();
            return back()->with('status', 'Removed from wishlist.');
        }

        Wishlist::create([
            'user_id' => Auth::id(),
            'movie_id' => $movie->id,
            'created_at' => now(),
        ]);

        return back()->with('status', 'Added to wishlist.');
    }

    public function cart(): View
    {
        $cart = $this->activeCart();
        $items = $cart
            ? $cart->items()->with(['show.movie', 'show.screen.theater', 'seat', 'category'])->get()
            : collect();

        return view('user.cart', [
            'cart' => $cart,
            'cartItems' => $items,
            'total' => (float) $items->sum('price'),
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $this->clearExpiredCarts();

        $data = $request->validate([
            'show_id' => ['required', 'integer', 'exists:shows,id'],
            'seats' => ['required', 'array', 'min:1', 'max:8'],
            'seats.*' => ['integer', 'distinct', 'exists:seats,id'],
            'ticket_type' => ['nullable', Rule::in(['adult', 'kid'])],
        ]);

        $show = Show::findOrFail($data['show_id']);
        if ($show->status !== 'scheduled' || $show->show_date->lt(now()->startOfDay())) {
            return back()->withErrors(['show_id' => 'This show is no longer available for booking.']);
        }

        $seatIds = array_values(array_unique($data['seats']));
        $ticketType = $data['ticket_type'] ?? 'adult';

        $availableSeats = DB::table('v_seat_availability')
            ->where('show_id', $show->id)
            ->whereIn('seat_id', $seatIds)
            ->where('seat_status', 'available')
            ->get()
            ->keyBy('seat_id');

        if ($availableSeats->count() !== count($seatIds)) {
            return back()->withErrors(['seats' => 'One or more selected seats are no longer available.']);
        }

        $cart = Cart::updateOrCreate(
            ['user_id' => Auth::id()],
            ['expires_at' => now()->addMinutes(10)]
        );

        try {
            foreach ($seatIds as $seatId) {
                $seat = $availableSeats[$seatId];

                CartItem::create([
                    'cart_id' => $cart->id,
                    'show_id' => $show->id,
                    'seat_id' => $seat->seat_id,
                    'seat_category_id' => $seat->seat_category_id,
                    'ticket_type' => $ticketType,
                    'price' => $this->seatPrice($seat, $ticketType),
                    'added_at' => now(),
                ]);
            }
        } catch (QueryException) {
            return back()->withErrors(['seats' => 'Those seats were just reserved. Please choose different seats.']);
        }

        return redirect()->route('user.cart')->with('status', 'Seats added to cart.');
    }

    public function checkout(): View
    {
        $cart = $this->activeCart();
        $items = $cart
            ? $cart->items()->with(['show.movie', 'show.screen.theater', 'seat', 'category'])->get()
            : collect();

        return view('user.checkout', [
            'cart' => $cart,
            'cartItems' => $items,
            'total' => (float) $items->sum('price'),
        ]);
    }

    public function placeBooking(Request $request): RedirectResponse
    {
        $this->clearExpiredCarts();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->forceFill(['name' => $data['name'], 'phone' => $data['phone']])->save();

        $cart = $this->activeCart();
        $items = $cart
            ? $cart->items()->with(['show', 'seat'])->get()
            : collect();

        if ($items->isEmpty()) {
            return redirect()->route('user.cart')->withErrors(['cart' => 'Your cart is empty or expired.']);
        }

        $showIds = $items->pluck('show_id')->unique();
        if ($showIds->count() !== 1) {
            return redirect()->route('user.cart')->withErrors(['cart' => 'Please checkout one show at a time.']);
        }

        try {
            $booking = DB::transaction(function () use ($items, $cart, $user) {
                $show = Show::query()->lockForUpdate()->findOrFail($items->first()->show_id);

                if ($show->status !== 'scheduled' || $show->show_date->lt(now()->startOfDay())) {
                    throw new \RuntimeException('show-unavailable');
                }

                $alreadyBooked = BookingSeat::query()
                    ->where('show_id', $show->id)
                    ->whereIn('seat_id', $items->pluck('seat_id'))
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyBooked) {
                    throw new \RuntimeException('seat-conflict');
                }

                $subtotal = (float) $items->sum('price');
                $adultCount = $items->where('ticket_type', 'adult')->count();
                $kidsCount = $items->where('ticket_type', 'kid')->count();

                $booking = Booking::create([
                    'booking_number' => $this->bookingNumber(),
                    'user_id' => $user->id,
                    'show_id' => $show->id,
                    'seat_count' => $items->count(),
                    'adult_count' => $adultCount,
                    'kids_count' => $kidsCount,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'total_amount' => $subtotal,
                    'payment_method' => 'cod',
                    'payment_status' => 'pending',
                    'booking_status' => 'confirmed',
                ]);

                foreach ($items as $item) {
                    BookingSeat::create([
                        'booking_id' => $booking->id,
                        'seat_id' => $item->seat_id,
                        'show_id' => $item->show_id,
                        'seat_category_id' => $item->seat_category_id,
                        'ticket_type' => $item->ticket_type,
                        'price_paid' => $item->price,
                        'ticket_number' => $booking->booking_number.'-'.$item->seat->row_label.$item->seat->seat_number,
                        'created_at' => now(),
                    ]);
                }

                Payment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => 'cod',
                    'amount' => $subtotal,
                    'status' => 'pending',
                    'notes' => 'Cash on delivery at cinema counter.',
                ]);

                $show->increment('booked_seats', $items->count());
                $cart->delete();

                return $booking;
            });
        } catch (QueryException|\RuntimeException $exception) {
            $message = $exception instanceof \RuntimeException && $exception->getMessage() === 'show-unavailable'
                ? 'This show is no longer available for booking.'
                : 'One or more selected seats are no longer available. Please choose different seats.';

            return redirect()->route('user.cart')->withErrors(['cart' => $message]);
        }

        return redirect()->route('user.booking.show', $booking->booking_number)->with('status', 'Booking confirmed.');
    }

    public function profile(): View
    {
        return view('user.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ]);

        $user->update($data);

        return back()->with('status', 'Profile updated.');
    }

    private function activeCart(): ?Cart
    {
        $this->clearExpiredCarts();

        return Cart::query()
            ->where('user_id', Auth::id())
            ->where('expires_at', '>', now())
            ->first();
    }

    private function clearExpiredCarts(): void
    {
        Cart::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }

    private function userBooking(string $number): Booking
    {
        return Booking::query()
            ->with(['show.movie', 'show.screen.theater', 'seats.seat', 'seats'])
            ->where('user_id', Auth::id())
            ->where('booking_number', $number)
            ->firstOrFail();
    }

    private function bookingNumber(): string
    {
        do {
            $number = 'BM-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        } while (Booking::where('booking_number', $number)->exists());

        return $number;
    }

    private function seatPrice(object $seat, string $ticketType): float
    {
        if ($ticketType === 'kid') {
            return (float) ($seat->kids_sale_price ?: $seat->kids_price ?: $seat->sale_price ?: $seat->price);
        }

        return (float) ($seat->sale_price ?: $seat->price);
    }
}
