<?php

namespace App\Services\Metrics;

class NullPublisher implements MetricsPublisher
{
    public function increment(string $metric, int $by = 1): void
    {
        // noop
    }

    public function gauge(string $metric, $value): void
    {
        // noop
    }
}
