<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    /*
    | Without keys, local development falls back to Google's published test
    | pair (https://developers.google.com/recaptcha/docs/faq): the checkbox
    | renders on any host, always passes, and says it is for testing. Real
    | keys (with the site's domain added in the reCAPTCHA console) are needed
    | everywhere else.
    */
    'recaptcha' => [
        'v2_site_key' => env('RECAPTCHA_V2_SITE_KEY') ?: (env('APP_ENV') === 'local' ? '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI' : null),
        'v2_secret_key' => env('RECAPTCHA_V2_SECRET_KEY') ?: (env('APP_ENV') === 'local' ? '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe' : null),
        'v3_site_key' => env('RECAPTCHA_V3_SITE_KEY'),
        'v3_secret_key' => env('RECAPTCHA_V3_SECRET_KEY'),
        'v3_min_score' => env('RECAPTCHA_V3_MIN_SCORE', 0.5),
        'skip_google_on_localhost' => (bool) env('RECAPTCHA_SKIP_GOOGLE_ON_LOCALHOST', false),
    ],

    /*
    | Wallet passes. Each button appears on the e-ticket only once its
    | credentials are set. Apple: a Pass Type ID certificate (.p12) from the
    | Apple Developer account plus Apple's WWDR intermediate (.pem). Google:
    | a Wallet API issuer ID and a service-account JSON key.
    */
    'wallet' => [
        'apple' => [
            'pass_type_id' => env('APPLE_WALLET_PASS_TYPE_ID'),
            'team_id' => env('APPLE_WALLET_TEAM_ID'),
            'certificate' => env('APPLE_WALLET_CERTIFICATE') ? base_path(env('APPLE_WALLET_CERTIFICATE')) : null,
            'password' => env('APPLE_WALLET_CERTIFICATE_PASSWORD'),
            'wwdr' => env('APPLE_WALLET_WWDR') ? base_path(env('APPLE_WALLET_WWDR')) : null,
        ],
        'google' => [
            'issuer_id' => env('GOOGLE_WALLET_ISSUER_ID'),
            'key_file' => env('GOOGLE_WALLET_KEY_FILE') ? base_path(env('GOOGLE_WALLET_KEY_FILE')) : null,
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
