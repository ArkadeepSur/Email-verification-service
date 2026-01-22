<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ThrottleBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_throttle_is_per_ip_by_default()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        // Use IP 1
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
                ->post(route('login.attempt'), [
                    'email' => $user->email,
                    'password' => 'wrong',
                ]);
            $resp->assertStatus(302)->assertSessionHasErrors(['email']);
        }

        // 6th from same IP should be throttled
        $throttled = $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
        $throttled->assertStatus(429);

        // Same email but different IP should NOT be throttled
        $respDifferentIp = $this->withServerVariables(['REMOTE_ADDR' => '5.6.7.8'])
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
        $respDifferentIp->assertStatus(302)->assertSessionHasErrors(['email']);
    }

    public function test_api_endpoint_throttling_behavior()
    {
        // Register a temporary rate-limited route
        Route::middleware('throttle:3,1')->post('/test-limited', function () {
            return response()->json(['ok' => true]);
        });

        // First three requests should succeed
        for ($i = 0; $i < 3; $i++) {
            $resp = $this->postJson('/test-limited');
            $resp->assertStatus(200)->assertJson(['ok' => true]);
        }

        // Fourth should be throttled
        $throttled = $this->postJson('/test-limited');
        $throttled->assertStatus(429);
    }
}

