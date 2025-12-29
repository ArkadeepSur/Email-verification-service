<?php

namespace App\Services\Metrics;

interface MetricsPublisher
{
    public function increment(string $metric, int $by = 1): void;

    public function gauge(string $metric, $value): void;
}
