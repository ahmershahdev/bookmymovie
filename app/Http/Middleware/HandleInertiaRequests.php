<?php

namespace App\Http\Middleware;

use App\Payments\PaymentGateway;
use App\Support\LayoutData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props every React page receives. Closures are only evaluated when the
     * page actually renders, and partial reloads can skip them.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => Str::before($user->name, ' ') ?: $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->profile_picture ? asset('storage/'.$user->profile_picture) : null,
                    'verified' => (bool) $user->email_verified_at,
                ] : null,
                'admin' => (bool) ($request->hasSession() && $request->session()->has('admin_id')),
                'adminReadOnly' => fn () => $request->hasSession() && $request->session()->has('admin_id')
                    && (bool) \App\Models\Admin::query()->find($request->session()->get('admin_id'))?->isReadOnly(),
            ],
            'locale' => app()->getLocale(),
            'site' => fn () => $this->site(),
            'counts' => fn () => LayoutData::counts(),
            'navMovies' => fn () => collect(LayoutData::navMovies())
                ->map(fn (array $movie) => collect($movie)->only(['id', 'title', 'slug', 'genre', 'duration', 'palette', 'poster_url', 'certificate', 'release_year', 'rating', 'reviews']))
                ->values(),
            'footer' => fn () => [
                'genres' => LayoutData::footerGenres(),
                'cinemas' => LayoutData::footerCinemas(),
            ],
            'giftCard' => fn () => $request->session()->get('giftCard'),
            'flash' => fn () => $request->session()->has('status')
                ? ['status' => $request->session()->get('status'), 'id' => (string) Str::uuid()]
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function site(): array
    {
        $settings = LayoutData::settings();

        $brand = fn (string $key) => filled($settings[$key] ?? null) ? $settings[$key] : \App\Models\SiteSetting::BRAND_DEFAULTS[$key];
        $logo = $brand('logo_path');

        return [
            'name' => $brand('site_name'),
            'tagline' => $brand('site_tagline'),
            'logo_url' => $logo ? asset(str_starts_with($logo, 'images/') ? $logo : 'storage/'.$logo) : asset('images/logo-sm.webp'),
            'support_email' => $brand('support_email'),
            'support_phone' => $brand('support_phone'),
            'contact_address' => $brand('contact_address'),
            'socials' => collect(['website' => 'Website', 'github' => 'GitHub', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'x' => 'X', 'youtube' => 'YouTube'])
                ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label, 'url' => $brand('social_'.$key)])
                ->filter(fn (array $social) => filled($social['url']))
                ->values()
                ->all(),
            'footer_description' => $settings['footer_description'] ?? 'An independent, open-source cinema booking platform for Pakistan.',
            'copyright_note' => $settings['copyright_note'] ?? 'By Syed Ahmer Shah · MIT licence',
            'response_sla' => $settings['response_sla'] ?? 'Within one working day',
            'service_area' => $settings['service_area'] ?? 'Pakistan',
            'contact_heading' => $settings['contact_heading'] ?? null,
            'contact_intro' => $settings['contact_intro'] ?? null,
            'catalog_heading' => $settings['catalog_heading'] ?? null,
            'catalog_intro' => $settings['catalog_intro'] ?? null,
            'max_seats' => (int) config('bookmymovie.booking.max_seats_per_booking', 4),
            'hold_minutes' => (int) config('bookmymovie.booking.cart_hold_minutes', 10),
            'payments' => array_column(PaymentGateway::available(), 'label'),
        ];
    }
}
