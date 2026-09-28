<?php

$sandbox = (bool) env('PAYMENTS_SANDBOX', true);

return [

    'sandbox' => $sandbox,

    // Hosts the browser may POST payment forms to (added to CSP form-action).
    'form_hosts' => $sandbox
        ? ['https://sandbox.jazzcash.com.pk', 'https://easypaystg.easypaisa.com.pk']
        : ['https://payments.jazzcash.com.pk', 'https://easypay.easypaisa.com.pk'],

    'jazzcash' => [
        'merchant_id' => env('JAZZCASH_MERCHANT_ID'),
        'password' => env('JAZZCASH_PASSWORD'),
        'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
        'endpoint' => $sandbox
            ? 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/'
            : 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/',
    ],

    'easypaisa' => [
        'store_id' => env('EASYPAISA_STORE_ID'),
        'hash_key' => env('EASYPAISA_HASH_KEY'),
        'endpoint' => $sandbox
            ? 'https://easypaystg.easypaisa.com.pk/easypay/Index.jsf'
            : 'https://easypay.easypaisa.com.pk/easypay/Index.jsf',
        'confirm_endpoint' => $sandbox
            ? 'https://easypaystg.easypaisa.com.pk/easypay/Confirm.jsf'
            : 'https://easypay.easypaisa.com.pk/easypay/Confirm.jsf',
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
