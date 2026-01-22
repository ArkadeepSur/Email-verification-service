<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from these domains / hosts will receive stateful API authentication
    | cookies. Typically you'll want to list the domains where your SPA is running.
    |
    */
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')),

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. If this value is null, personal access tokens do not
    | expire. This won't effect SPA session cookies.
    |
    */
    'expiration' => env('SANCTUM_EXPIRATION', null),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every sanctum route, giving you a
    | chance to modify the behavior of the sanctum endpoints.
    |
    */
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
