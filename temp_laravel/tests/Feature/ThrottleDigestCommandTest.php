<?php

namespace Tests\Feature;

use App\Models\ThrottleEvent;
use App\Notifications\ThrottleDigestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ThrottleDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_digest_command_sends_notification_with_attachment()
    {
        Notification::fake();

        // Create some events
        ThrottleEvent::create(['throttle_key' => 'k1', 'email' => 'a@example.com', 'ip' => '1.1.1.1', 'created_at' => now()->subMinutes(10)]);
        ThrottleEvent::create(['throttle_key' => 'k2', 'email' => 'b@example.com', 'ip' => '1.1.1.1', 'created_at' => now()->subMinutes(5)]);

        config(['admin.emails' => ['admin@example.com']]);

        $this->artisan('throttle:send-digest --window=day')->assertExitCode(0);

        Notification::assertSentTo(
            ['admin@example.com'],
            ThrottleDigestNotification::class
        );
    }
}
