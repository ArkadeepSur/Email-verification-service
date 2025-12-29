<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Models\ThrottleEvent;
use App\Notifications\ThrottleDigestNotification;

class SendThrottleDigest extends Command
{
    protected $signature = 'throttle:send-digest {--window=day : hour|day|week}';
    protected $description = 'Send a digest of throttle events to admins';

    public function handle()
    {
        $window = $this->option('window');

        switch ($window) {
            case 'hour': $since = now()->subHour(); break;
            case 'week': $since = now()->subWeek(); break;
            case 'day':
            default:
                $since = now()->subDay();
                break;
        }

        $events = ThrottleEvent::where('created_at', '>=', $since)->orderBy('created_at', 'desc')->get();

        if ($events->isEmpty()) {
            $this->info('No events in window; nothing to send.');
            return 0;
        }

        $total = $events->count();
        $byIp = $events->groupBy('ip')->map->count()->sortDesc()->take(10);

        $lines = [];
        $lines[] = "Throttle digest for last {$window} ({$total} events)";
        $lines[] = "Top IPs:";
        foreach ($byIp as $ip => $count) {
            $lines[] = " - {$ip}: {$count} events";
        }

        // Generate CSV attachment temporarily
        $tmp = sys_get_temp_dir() . '/throttle_digest_' . now()->format('Ymd_His') . '.csv';
        $fh = fopen($tmp, 'w');
        fputcsv($fh, ['time', 'ip', 'email', 'key']);
        foreach ($events as $e) {
            fputcsv($fh, [$e->created_at->toDateTimeString(), $e->ip, $e->email, $e->throttle_key]);
        }
        fclose($fh);

        $payload = ['subject' => "Throttle digest ({$window})", 'lines' => $lines];

        $adminEmails = config('admin.emails', []);
        foreach ($adminEmails as $email) {
            Notification::route('mail', $email)->notify(new ThrottleDigestNotification($payload, $tmp));
        }

        // Slack if configured
        if (!empty(config('admin.slack_webhook'))) {
            Notification::route('slack', config('admin.slack_webhook'))->notify(new ThrottleDigestNotification($payload, $tmp));
        }

        $this->info('Digest sent.');
        @unlink($tmp);

        return 0;
    }
}
