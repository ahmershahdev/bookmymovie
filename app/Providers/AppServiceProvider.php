<?php

namespace App\Providers;

use App\Support\LayoutData;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($proxies = config('bookmymovie.shield.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Resend needs an API key; without one, write mail to the log so
        // local development never fails on a missing credential.
        if (config('mail.default') === 'resend' && blank(config('services.resend.key'))) {
            config(['mail.default' => 'log']);
        }

        $this->configureRateLimiting();
        $this->shareLayoutData();
    }

    /**
     * Named limiters. Credential endpoints are keyed twice: by account+IP so
     * one attacker cannot lock a victim out from everywhere, and by IP alone
     * so one IP cannot spray passwords across many accounts.
     */
    private function configureRateLimiting(): void
    {
        $byUserOrIp = fn (Request $request) => (string) ($request->user()?->id ?: $request->ip());
        $emailKey = fn (Request $request) => strtolower(trim((string) $request->input('email'))).'|'.$request->ip();

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login:'.$emailKey($request)),
            Limit::perMinute(20)->by('login-ip:'.$request->ip()),
        ]);

        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by('admin:'.$emailKey($request)),
            Limit::perHour(20)->by('admin-ip:'.$request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => [
            Limit::perMinute(5)->by('register:'.$request->ip()),
            Limit::perDay(25)->by('register-day:'.$request->ip()),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by('reset:'.$emailKey($request)),
            Limit::perHour(15)->by('reset-ip:'.$request->ip()),
        ]);

        RateLimiter::for('verify-email', fn (Request $request) => [
            Limit::perMinute(6)->by('verify:'.$emailKey($request)),
        ]);

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(40)->by('search:'.$request->ip()));
        RateLimiter::for('cart-actions', fn (Request $request) => Limit::perMinute(12)->by('cart:'.$byUserOrIp($request)));
        RateLimiter::for('wishlist-actions', fn (Request $request) => Limit::perMinute(30)->by('wishlist:'.$byUserOrIp($request)));
        RateLimiter::for('checkout-actions', fn (Request $request) => Limit::perMinute(5)->by('checkout:'.$byUserOrIp($request)));
        RateLimiter::for('booking-changes', fn (Request $request) => Limit::perMinute(6)->by('booking:'.$byUserOrIp($request)));
        RateLimiter::for('profile-updates', fn (Request $request) => Limit::perMinute(6)->by('profile:'.$byUserOrIp($request)));
        RateLimiter::for('contact-form', fn (Request $request) => [
            Limit::perMinute(3)->by('contact:'.$byUserOrIp($request)),
            Limit::perDay(20)->by('contact-day:'.$request->ip()),
        ]);
    }

    private function shareLayoutData(): void
    {
        // The React pages get layout data as Inertia shared props; the few
        // Blade views left (root document, emails, error pages) only need
        // the public settings.
        View::composer(['app', 'partials.*', 'emails.*', 'errors.*'], fn ($view) => $view->with('siteSettings', LayoutData::settings()));
    }
}
