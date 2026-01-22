<?php

namespace App\Listeners;

use App\Events\ThrottleOccurred;
use App\Models\ThrottleEvent;
use App\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class LogAndAlertThrottleEvent
{
    public function handle(ThrottleOccurred $event)
    {
        // Persist the event
        ThrottleEvent::create([
            'throttle_key' => $event->key,
            'email' => $event->email,
            'ip' => $event->ip,
        ]);

        // Emit metrics for monitoring
        try {
            $metrics = app(\App\Services\Metrics\MetricsPublisher::class);
            $metrics->increment('throttle.events');
            $metrics->increment('throttle.lockouts');
        } catch (\Throwable $ex) {
            // ignore metrics errors
            Log::debug('Metrics publish failed: '.$ex->getMessage());
        }

        // Evaluate aggregation thresholds
        $windowMinutes = (int) config('admin.notify_window_minutes', 60);
        $distinctEmailThreshold = (int) config('admin.notify_distinct_ip_threshold', 3);
        $totalEventsThreshold = (int) config('admin.notify_total_events_threshold', 10);

        $since = now()->subMinutes($windowMinutes);

        // Distinct emails from same IP in window
        $distinctEmails = ThrottleEvent::where('ip', $event->ip)
            ->where('created_at', '>=', $since)
            ->distinct('email')
            ->pluck('email')
            ->filter()
            ->unique();

        if ($distinctEmails->count() >= $distinctEmailThreshold) {
            $subject = "Security Alert: {$distinctEmails->count()} distinct accounts locked from IP {$event->ip}";
            $lines = [
                "IP: {$event->ip}",
                'Distinct accounts: '.$distinctEmails->implode(', '),
                "Window: last {$windowMinutes} minute(s)",
            ];

            $this->notifyAdmins($subject, $lines);
            Log::warning($subject);

            return;
        }

        // Total events in window
        $totalEvents = ThrottleEvent::where('created_at', '>=', $since)->count();
        if ($totalEvents >= $totalEventsThreshold) {
            $subject = "Security Alert: {$totalEvents} throttle events in last {$windowMinutes} minutes";
            $lines = [
                "Total events: {$totalEvents}",
                "Window: last {$windowMinutes} minute(s)",
            ];

            $this->notifyAdmins($subject, $lines);
            Log::warning($subject);

            return;
        }
    }

    protected function notifyAdmins(string $subject, array $lines)
    {
        $adminEmails = config('admin.emails', []);
        if (empty($adminEmails)) {
            return;
        }

        $payload = ['subject' => $subject, 'lines' => $lines];

        try {
            foreach ($adminEmails as $email) {
                Notification::route('mail', $email)->notify(new AdminAlertNotification($payload));
            }

            // Send Slack if configured
            if (! empty(config('admin.slack_webhook'))) {
                Notification::route('slack', config('admin.slack_webhook'))->notify(new AdminAlertNotification($payload));
            }
        } catch (\Exception $ex) {
            Log::warning('Failed to send admin alert: '.$ex->getMessage());
        }
    }
}

