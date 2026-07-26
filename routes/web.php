<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
Route::get('/movies/{slug}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/movies/{slug}/book/{show}', [MovieController::class, 'seats'])->name('movies.seats');
Route::get('/compare', [PublicController::class, 'compare'])->name('movies.compare');
Route::match(['get', 'post'], '/search', [PublicController::class, 'search'])->name('search');

Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicController::class, 'submitContact'])->middleware('throttle:contact-form');
Route::get('/faq', [PublicController::class, 'faq'])->name('faq');
Route::get('/terms', [PublicController::class, 'terms'])->name('terms');
Route::get('/privacy', [PublicController::class, 'privacy'])->name('privacy');
Route::get('/refund-policy', [PublicController::class, 'refund'])->name('refund');
Route::get('/e-ticket-info', [PublicController::class, 'eticket'])->name('eticket.info');

Route::get('/login', [AuthController::class, 'showUserLogin'])->name('user.login');
Route::post('/login', [AuthController::class, 'userLogin'])->middleware('throttle:5,1');
Route::get('/register', [AuthController::class, 'showUserRegister'])->name('user.register');
Route::get('/signup', [AuthController::class, 'showUserRegister'])->name('user.signup');
Route::post('/register', [AuthController::class, 'userRegister'])->middleware('throttle:6,1');
Route::get('/verify-email', [AuthController::class, 'showEmailVerification'])->name('user.verify.notice');
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('user.verify')->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('user.logout');
Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirectToProvider'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('oauth.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('oauth.callback');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:6,1');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password/{token}', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:5,1');
Route::get('/admin/forgot-credentials', [AuthController::class, 'showAdminForgotCredentials'])->name('admin.credentials.request');
Route::post('/admin/forgot-credentials', [AuthController::class, 'sendAdminCredentialReset'])->middleware('throttle:3,1');
Route::get('/admin/reset-credentials/{token}', [AuthController::class, 'showAdminResetCredentials'])->name('admin.credentials.reset');
Route::post('/admin/reset-credentials/{token}', [AuthController::class, 'resetAdminCredentials'])->middleware('throttle:3,1');
Route::get('/admin/register', [AuthController::class, 'showAdminRegister'])->name('admin.register');
Route::post('/admin/register', [AuthController::class, 'adminRegister'])->middleware('throttle:6,1');
Route::post('/admin/logout', [AuthController::class, 'adminLogout'])->name('admin.logout');
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
Route::post('/admin/dashboard', [AdminController::class, 'handleDashboard']);

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/account/bookings', [AccountController::class, 'bookings'])->name('user.bookings');
    Route::get('/account/bookings/{number}', [AccountController::class, 'bookingShow'])->name('user.booking.show');
    Route::get('/account/bookings/{number}/track', [AccountController::class, 'tracking'])->name('user.tracking');
    Route::get('/account/wishlist', [AccountController::class, 'wishlist'])->name('user.wishlist');
    Route::post('/account/wishlist', [AccountController::class, 'addWishlist'])->middleware('throttle:wishlist-actions');
    Route::delete('/account/wishlist/{movie}', [AccountController::class, 'removeWishlist'])->name('user.wishlist.remove')->middleware('throttle:wishlist-actions');
    Route::get('/cart', [AccountController::class, 'cart'])->name('user.cart');
    Route::post('/cart', [AccountController::class, 'addToCart'])->middleware('throttle:cart-actions');
    Route::delete('/cart/{item}', [AccountController::class, 'removeCartItem'])->name('user.cart.remove')->middleware('throttle:cart-actions');
    Route::get('/checkout', [AccountController::class, 'checkout'])->name('user.checkout');
    Route::post('/checkout', [AccountController::class, 'placeBooking'])->middleware('throttle:checkout-actions');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('user.profile');
    Route::post('/account/profile', [AccountController::class, 'updateProfile']);
});

Route::fallback(fn () => response()->view('errors.404', [], 404))->name('404');
