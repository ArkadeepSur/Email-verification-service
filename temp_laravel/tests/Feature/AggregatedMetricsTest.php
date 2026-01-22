<?php

namespace Tests\Feature;

use App\Models\ThrottleEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AggregatedMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregated_endpoint_returns_expected_structure()
    {
        // Create events across two ips
        ThrottleEvent::create(['throttle_key' => 'k1', 'email' => 'a@example.com', 'ip' => '1.1.1.1', 'created_at' => now()->subMinutes(10)]);
        ThrottleEvent::create(['throttle_key' => 'k2', 'email' => 'b@example.com', 'ip' => '1.1.1.1', 'created_at' => now()->subMinutes(5)]);
        ThrottleEvent::create(['throttle_key' => 'k3', 'email' => 'c@example.com', 'ip' => '2.2.2.2', 'created_at' => now()->subMinutes(2)]);

        $resp = $this->getJson(route('admin.throttles.aggregated'));
        $resp->assertStatus(200);
        $resp->assertJsonStructure(['total_events', 'top_ips', 'distinct_accounts', 'grouped']);

        $json = $resp->json();
        $this->assertEquals(3, $json['total_events']);
        $this->assertTrue(is_array($json['top_ips']));
        $this->assertEquals(3, $json['distinct_accounts']);
    }
}
