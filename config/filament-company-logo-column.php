<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Logo.dev Publishable Key
    |--------------------------------------------------------------------------
    |
    | The package reads the key from `services.logo_dev.publishable_key` first
    | so it cleanly fits existing Laravel conventions. This value is used as a
    | fallback if that config key is not set.
    |
    */

    'publishable_key' => env('LOGO_DEV_PUBLISHABLE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Column Defaults
    |--------------------------------------------------------------------------
    |
    | Default values applied to every CompanyLogoColumn instance unless the
    | column overrides them via its fluent methods.
    |
    */

    'defaults' => [
        'size' => 40,
        'format' => 'webp',
        'theme' => 'light',
        'fallback' => 'monogram',
        'lazy' => true,
    ],

];
