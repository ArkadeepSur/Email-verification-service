<?php

namespace Tests\Feature;

use App\Events\ThrottleOccurred;
use App\Models\User;
use App\Notifications\LockoutNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ThrottleNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lockout_notification_sent_on_throttle_and_metrics_incremented()
    {
        Notification::fake();
        Event::fake();
        Cache::flush();

        $user = User::factory()->create([
            'email' => 'notify@example.com',
            'password' => bcrypt('secret123'),
        ]);

        // Trigger throttling (5 failed attempts + 6th triggers middleware notification)
        for ($i = 0; $i < 6; $i++) {
            $resp = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
        }

        // 6th response should be throttled
        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertStatus(429);

        // Notification should be sent to the user (LockoutNotification)
        Notification::assertSentTo($user, LockoutNotification::class);

        // Event should be dispatched
        Event::assertDispatched(ThrottleOccurred::class);

        // Cache metric should be incremented
        $this->assertGreaterThan(0, Cache::get('throttle.events.count', 0));
    }

    public function test_single_notification_is_only_sent_once_within_decay_window()
    {
        Notification::fake();
        Cache::flush();

        $user = User::factory()->create([
            'email' => 'notify2@example.com',
            'password' => bcrypt('secret123'),
        ]);

        // Cause throttling twice; because we set a cache sentinel, notification should only be sent once
        for ($i = 0; $i < 7; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
        }

        Notification::assertSentTimes(LockoutNotification::class, 1);
    }
}

