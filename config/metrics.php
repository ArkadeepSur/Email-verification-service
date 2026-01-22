<?php

use Illuminate\Support\Str;

return [
    'driver' => env('METRICS_DRIVER', 'null'), // supported: null, statsd

    'statsd' => [
        'host' => env('STATSD_HOST', '127.0.0.1'),
        'port' => env('STATSD_PORT', 8125),
        'prefix' => env('STATSD_PREFIX', env('APP_NAME') ? Str::slug(env('APP_NAME')).'.' : ''),
    ],
];
