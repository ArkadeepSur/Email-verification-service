<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_is_throttled_after_failed_attempts()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ]);

            $resp->assertStatus(401);
        }

        // 6th should be throttled
        $throttled = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);

        $throttled->assertStatus(429);
    }

    public function test_successful_api_login_resets_throttle_counter()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $resp = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
            $resp->assertStatus(401);
        }

        // Successful login
        $ok = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $ok->assertStatus(200);
        $this->assertArrayHasKey('token', $ok->json());

        // After successful login, failed attempts should start fresh
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
            $resp->assertStatus(401);
        }

        // 6th should be throttled
        $throttled = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
        $throttled->assertStatus(429);
    }
}

