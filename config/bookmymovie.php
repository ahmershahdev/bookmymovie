<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public identity
    |--------------------------------------------------------------------------
    |
    | The canonical origin is used for canonical links, Open Graph URLs, the
    | generated sitemap and JSON-LD. Keep it in sync with the production host.
    |
    */

    'canonical_url' => rtrim((string) env('BOOKMYMOVIE_CANONICAL_URL', 'https://bookmymovie.ahmershah.dev'), '/'),

    'currency' => env('BOOKMYMOVIE_CURRENCY', 'PKR'),

    'timezone' => env('BOOKMYMOVIE_TIMEZONE', 'Asia/Karachi'),

    /*
    |--------------------------------------------------------------------------
    | Booking rules
    |--------------------------------------------------------------------------
    */

    'booking' => [
        'max_seats_per_booking' => (int) env('BOOKMYMOVIE_MAX_SEATS', 4),
        'cart_hold_minutes' => (int) env('BOOKMYMOVIE_CART_HOLD_MINUTES', 10),
        // Customers may cancel an unpaid booking until this many minutes before the show.
        'cancellation_cutoff_minutes' => (int) env('BOOKMYMOVIE_CANCELLATION_CUTOFF', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin onboarding secrets
    |--------------------------------------------------------------------------
    |
    | Both features are disabled when the secret is empty. There is deliberately
    | no default value: a guessable fallback would let anyone create an admin.
    |
    */

    'admin' => [
        'invite_code' => env('BOOKMYMOVIE_ADMIN_INVITE'),
        'recovery_secret' => env('BOOKMYMOVIE_ADMIN_RECOVERY_SECRET'),

        /*
        | The owner's seeded admin account, with full access. Set
        | BOOKMYMOVIE_DEMO_ADMIN_READ_ONLY=true to turn it into a public demo:
        | it then refuses every change, and only then are its credentials
        | shown on the admin sign-in page. Change the password with
        | BOOKMYMOVIE_ADMIN_PASSWORD before going live.
        */
        'demo_enabled' => (bool) env('BOOKMYMOVIE_DEMO_ADMIN', true),
        'demo_read_only' => (bool) env('BOOKMYMOVIE_DEMO_ADMIN_READ_ONLY', false),
        'demo_email' => 'admin@bookmymovie.ahmershah.dev',
        'demo_password' => (string) env('BOOKMYMOVIE_ADMIN_PASSWORD', 'Admin@1234'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Abuse, DoS and brute-force protection
    |--------------------------------------------------------------------------
    |
    | Application-level shielding only. Volumetric DDoS must be absorbed at the
    | edge (Cloudflare, a load balancer or the host firewall); see SECURITY.md.
    |
    */

    'shield' => [
        'enabled' => (bool) env('SHIELD_ENABLED', true),
        // Requests per minute per IP across the whole site.
        'requests_per_minute' => (int) env('SHIELD_REQUESTS_PER_MINUTE', 240),
        // Burst window: requests allowed per IP inside 10 seconds.
        'burst_per_10_seconds' => (int) env('SHIELD_BURST_PER_10_SECONDS', 60),
        // Strikes (limit breaches or scanner hits) before an IP is banned.
        'strikes_before_ban' => (int) env('SHIELD_STRIKES_BEFORE_BAN', 5),
        'ban_minutes' => (int) env('SHIELD_BAN_MINUTES', 30),
        // Largest accepted request body in kilobytes (uploads are capped separately).
        'max_body_kb' => (int) env('SHIELD_MAX_BODY_KB', 6144),
        // Comma separated IPs that are never throttled (health checks, office IPs).
        'allowlist' => array_filter(array_map('trim', explode(',', (string) env('SHIELD_ALLOWLIST', '')))),
        // Set to your CDN / load balancer ranges (or "*" behind a trusted edge)
        // so rate limits and bans see the real client IP, not the proxy's.
        'trusted_proxies' => env('TRUSTED_PROXIES'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Form hardening
    |--------------------------------------------------------------------------
    */

    'forms' => [
        // A human cannot fill a form faster than this. Bots usually do.
        'minimum_fill_seconds' => (int) env('FORM_MINIMUM_FILL_SECONDS', 3),
    ],

];
