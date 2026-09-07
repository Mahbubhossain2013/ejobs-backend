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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'onipay' => [
        'webhook_secret' => env('ONIPAY_WEBHOOK_SECRET', ''),
    ],

    'logo_dev' => [
        'publishable_key' => env('LOGO_DEV_PUBLISHABLE_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/api/auth/google/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL') . '/api/auth/facebook/callback'),
    ],

    'bkash' => [
        'base_url' => env('BKASH_BASE_URL', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'),
        'username' => env('BKASH_USERNAME', ''),
        'password' => env('BKASH_PASSWORD', ''),
        'app_key' => env('BKASH_APP_KEY', ''),
        'app_secret' => env('BKASH_APP_SECRET', ''),
        'callback_url' => env('BKASH_CALLBACK_URL', ''),
    ],

    'sslcommerz' => [
        'store_id' => env('SSLCZ_STORE_ID', ''),
        'store_password' => env('SSLCZ_STORE_PASSWORD', ''),
        'sandbox' => env('SSLCZ_SANDBOX', true),
        'callback_url' => env('SSLCZ_CALLBACK_URL', ''),
    ],

    'nagad' => [
        'sandbox' => env('NAGAD_SANDBOX', true),
    ],

    'rocket' => [
        'sandbox' => env('ROCKET_SANDBOX', true),
    ],

    'eps' => [
        'base_url_sandbox' => env('EPS_BASE_URL_SANDBOX', 'https://sandboxpgapi.eps.com.bd/v1'),
        'base_url_live'    => env('EPS_BASE_URL_LIVE', 'https://pgapi.eps.com.bd/v1'),
        'callback_url'     => env('EPS_CALLBACK_URL', ''),
    ],

    'chrome_binary_path' => env('CHROME_BINARY_PATH', ''),

];
