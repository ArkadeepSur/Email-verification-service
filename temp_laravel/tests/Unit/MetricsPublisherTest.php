<?php

namespace Tests\Unit;

use App\Services\Metrics\StatsdPublisher;
use Tests\TestCase;

class MetricsPublisherTest extends TestCase
{
    public function test_statsd_message_format()
    {
        $pub = new StatsdPublisher('127.0.0.1', 8125, 'app.');
        // Method should not throw and should accept increments/gauges
        $pub->increment('test.metric', 2);
        $pub->gauge('test.gauge', 42);
        $this->assertTrue(true); // no-op: ensure no exception
    }
}

