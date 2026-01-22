<?php

return [
    'emails' => array_filter(array_map('trim', explode(',', env('ADMIN_EMAILS', 'admin@example.com')))),
    'slack_webhook' => env('ADMIN_SLACK_WEBHOOK', null),
    'notify_distinct_ip_threshold' => env('ADMIN_NOTIFY_DISTINCT_IP_THRESHOLD', 3),
    'notify_total_events_threshold' => env('ADMIN_NOTIFY_TOTAL_EVENTS_THRESHOLD', 10),
    'notify_window_minutes' => env('ADMIN_NOTIFY_WINDOW_MINUTES', 60),
];

