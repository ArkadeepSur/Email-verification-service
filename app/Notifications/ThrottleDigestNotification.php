<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class ThrottleDigestNotification extends Notification
{
    use Queueable;

    public array $payload;
    public ?string $csvPath;

    public function __construct(array $payload, ?string $csvPath = null)
    {
        $this->payload = $payload;
        $this->csvPath = $csvPath;
    }

    public function via($notifiable)
    {
        $channels = ['mail'];
        if (!empty(config('admin.slack_webhook'))) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail($notifiable)
    {
        $m = new MailMessage;
        $m->subject($this->payload['subject'] ?? 'Throttle digest');

        foreach (($this->payload['lines'] ?? []) as $line) {
            $m->line($line);
        }

        if ($this->csvPath && file_exists($this->csvPath)) {
            $m->attach($this->csvPath, ['as' => basename($this->csvPath), 'mime' => 'text/csv']);
        }

        return $m;
    }

    public function toSlack($notifiable)
    {
        if (!class_exists(SlackMessage::class)) {
            return null;
        }

        $msg = (new SlackMessage)->from(config('app.name'));
        $msg->content(($this->payload['subject'] ?? 'Throttle digest') . "\n" . implode("\n", ($this->payload['lines'] ?? [])));
        return $msg;
    }
}
