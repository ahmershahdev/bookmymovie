<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CinemaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| URL style
|--------------------------------------------------------------------------
| Every page a person sees has a clean, path-only address. Filters that only
| narrow what is already on screen (genre, language, sort, dates) run in
| the browser, so they never add ?query=strings. Query strings only remain
| where an outside service dictates them (OAuth and payment callbacks).
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
| Catalogue
*/

Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
Route::get('/movies/{status}', [MovieController::class, 'status'])->whereIn('status', ['now-showing', 'coming-soon', 'ended'])->name('movies.status');
Route::get('/genres/{slug}', [MovieController::class, 'genre'])->where('slug', '[a-z0-9-]+')->name('movies.genre');
Route::get('/movies/{slug}', [MovieController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('movies.show');
Route::get('/movies/{slug}/book/{show}', [MovieController::class, 'seats'])->whereNumber('show')->name('movies.seats');
Route::get('/compare', [PublicController::class, 'compare'])->name('movies.compare');
Route::get('/search/{query?}', [PublicController::class, 'search'])->where('query', '[^/]{1,120}')->middleware('throttle:search')->name('search');

Route::get('/cinemas', [CinemaController::class, 'index'])->name('cinemas.index');
Route::get('/cinemas/{theater:slug}', [CinemaController::class, 'show'])->name('cinemas.show');
Route::get('/offers', [PublicController::class, 'offers'])->name('offers');

/*
| Company, help and legal
*/

Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicController::class, 'submitContact'])->middleware('throttle:contact-form');
Route::get('/faq', [PublicController::class, 'faq'])->name('faq');
Route::get('/terms', [PublicController::class, 'terms'])->name('terms');
Route::get('/privacy', [PublicController::class, 'privacy'])->name('privacy');
Route::get('/refund-policy', [PublicController::class, 'refund'])->name('refund');
Route::get('/cookie-policy', [PublicController::class, 'cookies'])->name('cookies');
Route::get('/accessibility', [PublicController::class, 'accessibility'])->name('accessibility');
Route::get('/e-ticket-info', [PublicController::class, 'eticket'])->name('eticket.info');

/*
| Customer authentication
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showUserLogin'])->name('user.login');
    Route::post('/login', [AuthController::class, 'userLogin'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showUserRegister'])->name('user.register');
    Route::redirect('/signup', '/register', 301)->name('user.signup');
    Route::post('/register', [AuthController::class, 'userRegister'])->middleware('throttle:register');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:password-reset');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
});

Route::get('/verify-email', [AuthController::class, 'showEmailVerification'])->name('user.verify.notice');
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('user.verify')->middleware('throttle:verify-email');
Route::post('/verify-email/resend', [AuthController::class, 'resendVerification'])->name('user.verify.resend')->middleware('throttle:password-reset');
Route::post('/logout', [AuthController::class, 'logout'])->name('user.logout');

Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirectToProvider'])
    ->whereIn('provider', ['google', 'facebook'])
    ->middleware('throttle:login')
    ->name('oauth.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])
    ->whereIn('provider', ['google', 'facebook'])
    ->middleware('throttle:login')
    ->name('oauth.callback');

/*
| Admin
*/

Route::prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'adminLogin'])->middleware('throttle:admin-login');
    Route::get('/forgot-credentials', [AuthController::class, 'showAdminForgotCredentials'])->name('admin.credentials.request');
    Route::post('/forgot-credentials', [AuthController::class, 'sendAdminCredentialReset'])->middleware('throttle:admin-login');
    Route::get('/reset-credentials/{token}', [AuthController::class, 'showAdminResetCredentials'])->name('admin.credentials.reset');
    Route::post('/reset-credentials/{token}', [AuthController::class, 'resetAdminCredentials'])->middleware('throttle:admin-login');
    Route::get('/register', [AuthController::class, 'showAdminRegister'])->name('admin.register');
    Route::post('/register', [AuthController::class, 'adminRegister'])->middleware('throttle:admin-login');
    Route::post('/logout', [AuthController::class, 'adminLogout'])->name('admin.logout');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard/movies/{movie}', [AdminController::class, 'dashboard'])->whereNumber('movie')->name('admin.dashboard.movie');
    Route::post('/dashboard', [AdminController::class, 'handleDashboard'])->middleware('throttle:30,1');
});

/*
| Customer account, cart, checkout and payments
*/

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/account/bookings', [AccountController::class, 'bookings'])->name('user.bookings');
    Route::get('/account/bookings/{number}', [AccountController::class, 'bookingShow'])->name('user.booking.show');
    Route::get('/account/bookings/{number}/track', [AccountController::class, 'tracking'])->name('user.tracking');
    Route::post('/account/bookings/{number}/cancel', [AccountController::class, 'cancelBooking'])->name('user.booking.cancel')->middleware('throttle:booking-changes');
    Route::get('/account/wishlist', [AccountController::class, 'wishlist'])->name('user.wishlist');
    Route::post('/account/wishlist', [AccountController::class, 'addWishlist'])->middleware('throttle:wishlist-actions');
    Route::delete('/account/wishlist/{movie}', [AccountController::class, 'removeWishlist'])->name('user.wishlist.remove')->middleware('throttle:wishlist-actions');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('user.profile');
    Route::post('/account/profile', [AccountController::class, 'updateProfile'])->middleware('throttle:profile-updates');

    Route::get('/cart', [AccountController::class, 'cart'])->name('user.cart');
    Route::post('/cart', [AccountController::class, 'addToCart'])->middleware('throttle:cart-actions');
    Route::delete('/cart', [AccountController::class, 'clearCart'])->name('user.cart.clear')->middleware('throttle:cart-actions');
    Route::delete('/cart/{item}', [AccountController::class, 'removeCartItem'])->name('user.cart.remove')->middleware('throttle:cart-actions');
    Route::get('/checkout', [AccountController::class, 'checkout'])->name('user.checkout');
    Route::post('/checkout', [AccountController::class, 'placeBooking'])->middleware('throttle:checkout-actions');

    Route::get('/payments/{number}/pay', [PaymentController::class, 'start'])->name('payments.start')->middleware('throttle:checkout-actions');

    Route::post('/movies/{slug}/reviews', [MovieController::class, 'storeReview'])->name('movies.reviews.store')->middleware('throttle:profile-updates');
});

// Reached by the payment providers; verified by signature or API lookup, not by session.
Route::post('/payments/jazzcash/callback', [PaymentController::class, 'jazzcash'])->name('payments.jazzcash.callback')->middleware('throttle:60,1');
Route::match(['get', 'post'], '/payments/easypaisa/{number}/confirm', [PaymentController::class, 'easypaisaConfirm'])->name('payments.easypaisa.confirm')->middleware('throttle:60,1');
Route::get('/payments/easypaisa/ipn', [PaymentController::class, 'easypaisaIpn'])->name('payments.easypaisa.ipn')->middleware('throttle:60,1');
Route::match(['get', 'post'], '/payments/{number}/return', [PaymentController::class, 'back'])->name('payments.return')->middleware('throttle:60,1');

Route::fallback(fn () => abort(404));
