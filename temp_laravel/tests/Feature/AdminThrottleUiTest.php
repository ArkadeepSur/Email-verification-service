<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ThrottleEvent;

class AdminThrottleUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_throttle_events()
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $user = User::factory()->create(['email' => 'victim@example.com']);

        // Create sample events
        ThrottleEvent::create(['throttle_key' => 'a|1.2.3.4', 'email' => 'victim@example.com', 'ip' => '1.2.3.4']);

        $resp = $this->actingAs($admin)->get(route('admin.throttles'));
        $resp->assertStatus(200);
        $resp->assertSee('Throttle Events');
        $resp->assertSee('1.2.3.4');
        $resp->assertSee('victim@example.com');
        $resp->assertSee('Export PNG');

        // Export CSV
        $csv = $this->actingAs($admin)->get(route('admin.throttles.export'));
        $csv->assertStatus(200);
        $csv->assertHeader('Content-Type', 'text/csv');

        // Chart data endpoint
        $data = $this->actingAs($admin)->getJson(route('admin.throttles.data'));
        $data->assertStatus(200);
        $json = $data->json();
        $this->assertArrayHasKey('labels', $json);
        $this->assertArrayHasKey('counts', $json);
        $this->assertArrayHasKey('top_ips', $json);
    }

    public function test_non_admin_cannot_view_page()
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $resp = $this->actingAs($user)->get(route('admin.throttles'));
        $resp->assertStatus(403);
    }
}
