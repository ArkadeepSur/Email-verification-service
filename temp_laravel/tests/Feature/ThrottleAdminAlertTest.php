<?php

namespace Tests\Feature;

use App\Events\ThrottleOccurred;
use App\Notifications\AdminAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ThrottleAdminAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_receive_alert_when_threshold_exceeded()
    {
        Notification::fake();
        Event::fake();

        // Set admin emails via config
        config(['admin.emails' => ['admin@example.com']]);

        // Dispatch many throttle occurred events from same IP for distinct emails
        for ($i = 0; $i < 4; $i++) {
            event(new ThrottleOccurred('key'.$i, 'user'.$i.'@example.com', '9.9.9.9'));
        }

        // The listener should send admin alerts (LogAndAlertThrottleEvent triggers)
        Notification::assertSentTo(
            ['admin@example.com'],
            AdminAlertNotification::class
        );
    }
}

