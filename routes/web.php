<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminCommerceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminOperationsController;
use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CinemaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TicketController;
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
Route::get('/u/{username}', [\App\Http\Controllers\ProfileController::class, 'show'])->where('username', '[a-z0-9_]{3,30}')->name('profile.show');
Route::get('/movies/{slug}/reviews', [MovieController::class, 'reviews'])->where('slug', '[a-z0-9-]+')->name('movies.reviews')->middleware('throttle:60,1');
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
Route::get('/gift-cards', [PublicController::class, 'giftCards'])->name('gift-cards');
Route::post('/gift-cards/balance', [PublicController::class, 'giftCardBalance'])->name('gift-cards.balance')->middleware('throttle:10,1');
Route::get('/assistant/context', [\App\Http\Controllers\AssistantController::class, 'context'])->name('assistant.context')->middleware('throttle:30,1');
Route::post('/language', [LocaleController::class, 'update'])->name('locale.update')->middleware('throttle:30,1');

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
    Route::get('/username-check', [AuthController::class, 'usernameAvailable'])->name('username.check')->middleware('throttle:40,1');
    Route::get('/login/code', [AuthController::class, 'showTwoFactor'])->name('user.two-factor');
    Route::post('/login/code', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:login');
    Route::post('/login/code/resend', [AuthController::class, 'resendTwoFactor'])->name('user.two-factor.resend')->middleware('throttle:password-reset');
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

Route::prefix('admin')->middleware([\App\Http\Middleware\BlockDemoAdminWrites::class, \App\Http\Middleware\AuthorizeAdminRole::class])->group(function () {
    Route::get('/login/code', [AdminStaffController::class, 'challenge'])->name('admin.two-factor');
    Route::post('/login/code', [AdminStaffController::class, 'verify'])->name('admin.two-factor.verify')->middleware('throttle:admin-login');
    Route::get('/analytics', [\App\Http\Controllers\AdminAnalyticsController::class, 'index'])->name('admin.analytics');
    Route::get('/staff', [AdminStaffController::class, 'index'])->name('admin.staff');
    Route::post('/staff', [AdminStaffController::class, 'store'])->name('admin.staff.store')->middleware('throttle:20,1');
    Route::put('/staff/{staff}', [AdminStaffController::class, 'update'])->whereNumber('staff')->name('admin.staff.update')->middleware('throttle:30,1');
    Route::post('/staff/{staff}/reset-2fa', [AdminStaffController::class, 'resetTwoFactor'])->whereNumber('staff')->name('admin.staff.reset-2fa')->middleware('throttle:10,1');
    Route::get('/security', [AdminStaffController::class, 'security'])->name('admin.security');
    Route::post('/security/2fa', [AdminStaffController::class, 'beginSetup'])->name('admin.security.begin')->middleware('throttle:10,1');
    Route::post('/security/2fa/confirm', [AdminStaffController::class, 'confirmSetup'])->name('admin.security.confirm')->middleware('throttle:10,1');
    Route::delete('/security/2fa', [AdminStaffController::class, 'disable'])->name('admin.security.disable')->middleware('throttle:10,1');
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

    Route::get('/activity', [AdminOperationsController::class, 'activity'])->name('admin.activity');
    Route::get('/users', [AdminPortalController::class, 'users'])->name('admin.users');
    Route::get('/users/{user}', [AdminPortalController::class, 'userShow'])->whereNumber('user')->name('admin.users.show');
    Route::post('/users/{user}/ban', [AdminPortalController::class, 'banUser'])->whereNumber('user')->name('admin.users.ban')->middleware('throttle:30,1');
    Route::post('/users/{user}/unban', [AdminPortalController::class, 'unbanUser'])->whereNumber('user')->name('admin.users.unban')->middleware('throttle:30,1');
    Route::post('/banned-ips', [AdminPortalController::class, 'banIp'])->name('admin.ips.store')->middleware('throttle:30,1');
    Route::delete('/banned-ips/{ban}', [AdminPortalController::class, 'unbanIp'])->whereNumber('ban')->name('admin.ips.destroy')->middleware('throttle:30,1');
    Route::delete('/banned-devices/{device}', [AdminPortalController::class, 'unbanDevice'])->whereNumber('device')->name('admin.devices.destroy')->middleware('throttle:30,1');
    Route::get('/commerce', [AdminCommerceController::class, 'index'])->name('admin.commerce');
    Route::post('/coupons', [AdminCommerceController::class, 'storeCoupon'])->name('admin.coupons.store')->middleware('throttle:30,1');
    Route::put('/coupons/{coupon}', [AdminCommerceController::class, 'updateCoupon'])->whereNumber('coupon')->name('admin.coupons.update')->middleware('throttle:60,1');
    Route::post('/coupons/{coupon}/toggle', [AdminCommerceController::class, 'toggleCoupon'])->whereNumber('coupon')->name('admin.coupons.toggle')->middleware('throttle:60,1');
    Route::delete('/coupons/{coupon}', [AdminCommerceController::class, 'destroyCoupon'])->whereNumber('coupon')->name('admin.coupons.destroy')->middleware('throttle:30,1');
    Route::put('/snacks/{concession}', [AdminCommerceController::class, 'updateSnack'])->whereNumber('concession')->name('admin.snacks.update')->middleware('throttle:60,1');
    Route::put('/films/{movie}/access', [AdminCommerceController::class, 'updateFilm'])->whereNumber('movie')->name('admin.films.access')->middleware('throttle:60,1');
    Route::get('/reviews', [AdminPortalController::class, 'reviews'])->name('admin.reviews');
    Route::post('/reviews/{review}', [AdminPortalController::class, 'moderateReview'])->whereNumber('review')->name('admin.reviews.moderate')->middleware('throttle:60,1');
    Route::get('/settings', [AdminPortalController::class, 'settings'])->name('admin.settings');
    Route::post('/settings', [AdminPortalController::class, 'updateSettings'])->name('admin.settings.update')->middleware('throttle:20,1');
    Route::post('/bookings/{number}/refund', [AdminOperationsController::class, 'refund'])->name('admin.bookings.refund')->middleware('throttle:30,1');
    Route::post('/gift-cards', [AdminOperationsController::class, 'issueGiftCard'])->name('admin.gift-cards.store')->middleware('throttle:30,1');
    Route::delete('/gift-cards/{card}', [AdminOperationsController::class, 'deactivateGiftCard'])->name('admin.gift-cards.destroy')->middleware('throttle:30,1');
    // Printed in every e-ticket QR code; the signature stops forged numbers.
    Route::get('/tickets/{number}/{signature}', [AdminOperationsController::class, 'verifyTicket'])->where('signature', '[a-f0-9]{16}')->name('admin.tickets.verify');
    Route::post('/tickets/{number}/{signature}/admit', [AdminOperationsController::class, 'admit'])->where('signature', '[a-f0-9]{16}')->name('admin.tickets.admit')->middleware('throttle:60,1');
});

/*
| Customer account, cart, checkout and payments
*/

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/account/bookings', [AccountController::class, 'bookings'])->name('user.bookings');
    Route::get('/account/bookings/{number}', [AccountController::class, 'bookingShow'])->name('user.booking.show');
    Route::get('/account/bookings/{number}/track', [AccountController::class, 'tracking'])->name('user.tracking');
    Route::get('/account/bookings/{number}/wallet/apple', [TicketController::class, 'apple'])->name('tickets.wallet.apple')->middleware('throttle:profile-updates');
    Route::get('/account/bookings/{number}/wallet/google', [TicketController::class, 'google'])->name('tickets.wallet.google')->middleware('throttle:profile-updates');
    Route::post('/account/bookings/{number}/split', [\App\Http\Controllers\SplitController::class, 'store'])->name('user.booking.split')->middleware('throttle:booking-changes');
    Route::delete('/account/bookings/{number}/split', [\App\Http\Controllers\SplitController::class, 'destroy'])->name('user.booking.split.destroy')->middleware('throttle:booking-changes');
    Route::post('/account/bookings/{number}/cancel', [AccountController::class, 'cancelBooking'])->name('user.booking.cancel')->middleware('throttle:booking-changes');
    Route::get('/account/wishlist', [AccountController::class, 'wishlist'])->name('user.wishlist');
    Route::post('/account/wishlist', [AccountController::class, 'addWishlist'])->middleware('throttle:wishlist-actions');
    Route::delete('/account/wishlist/{movie}', [AccountController::class, 'removeWishlist'])->name('user.wishlist.remove')->middleware('throttle:wishlist-actions');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('user.profile');
    Route::post('/account/profile', [AccountController::class, 'updateProfile'])->middleware('throttle:profile-updates');

    Route::get('/cart', [AccountController::class, 'cart'])->name('user.cart');
    Route::post('/cart', [AccountController::class, 'addToCart'])->middleware('throttle:cart-actions');
    Route::delete('/cart', [AccountController::class, 'clearCart'])->name('user.cart.clear')->middleware('throttle:cart-actions');
    Route::patch('/cart/{item}', [AccountController::class, 'updateCartItem'])->name('user.cart.item')->middleware('throttle:cart-actions');
    Route::delete('/cart/{item}', [AccountController::class, 'removeCartItem'])->name('user.cart.remove')->middleware('throttle:cart-actions');
    Route::get('/checkout', [AccountController::class, 'checkout'])->name('user.checkout');
    Route::post('/checkout', [AccountController::class, 'placeBooking'])->middleware('throttle:checkout-actions');

    Route::get('/payments/{number}/pay', [PaymentController::class, 'start'])->name('payments.start')->middleware('throttle:checkout-actions');

    Route::post('/push/subscribe', [\App\Http\Controllers\EngagementController::class, 'subscribe'])->name('push.subscribe')->middleware('throttle:20,1');
    Route::delete('/push/subscribe', [\App\Http\Controllers\EngagementController::class, 'unsubscribe'])->name('push.unsubscribe')->middleware('throttle:20,1');
    Route::post('/shows/{show}/waitlist', [\App\Http\Controllers\EngagementController::class, 'joinWaitlist'])->whereNumber('show')->name('waitlist.join')->middleware('throttle:20,1');
    Route::delete('/shows/{show}/waitlist', [\App\Http\Controllers\EngagementController::class, 'leaveWaitlist'])->whereNumber('show')->name('waitlist.leave')->middleware('throttle:20,1');

    Route::post('/movies/{slug}/reviews', [MovieController::class, 'storeReview'])->name('movies.reviews.store')->middleware('throttle:profile-updates');
    Route::post('/reviews/{review}/helpful', [MovieController::class, 'voteReview'])->whereNumber('review')->name('reviews.helpful')->middleware('throttle:30,1');
});

// Split-the-bill shares: private links, no account needed to pay a share.
Route::get('/split/{token}', [\App\Http\Controllers\SplitController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->name('split.show')->middleware('throttle:60,1');
Route::post('/split/{token}/pay', [\App\Http\Controllers\SplitController::class, 'pay'])->where('token', '[A-Za-z0-9]{40}')->name('split.pay')->middleware('throttle:10,1');
Route::get('/split/{token}/return', [\App\Http\Controllers\SplitController::class, 'back'])->where('token', '[A-Za-z0-9]{40}')->name('split.return')->middleware('throttle:30,1');

// Reached by the payment providers; verified by signature or API lookup, not by session.
Route::post('/payments/jazzcash/callback', [PaymentController::class, 'jazzcash'])->name('payments.jazzcash.callback')->middleware('throttle:60,1');
Route::match(['get', 'post'], '/payments/easypaisa/{number}/confirm', [PaymentController::class, 'easypaisaConfirm'])->name('payments.easypaisa.confirm')->middleware('throttle:60,1');
Route::get('/payments/easypaisa/ipn', [PaymentController::class, 'easypaisaIpn'])->name('payments.easypaisa.ipn')->middleware('throttle:60,1');
Route::match(['get', 'post'], '/payments/{number}/return', [PaymentController::class, 'back'])->name('payments.return')->middleware('throttle:60,1');

Route::fallback(fn () => abort(404));
