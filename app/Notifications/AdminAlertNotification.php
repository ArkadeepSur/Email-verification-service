<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class AdminAlertNotification extends Notification
{
    use Queueable;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function via($notifiable)
    {
        $channels = ['mail'];
        if (! empty(config('admin.slack_webhook'))) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail($notifiable)
    {
        $m = new MailMessage;
        $m->subject($this->payload['subject'] ?? 'Security alert: multiple lockouts');
        foreach (($this->payload['lines'] ?? []) as $line) {
            $m->line($line);
        }

        return $m;
    }

    public function toSlack($notifiable)
    {
        if (! class_exists(SlackMessage::class)) {
            return null;
        }

        $msg = new SlackMessage;
        $msg->from(config('app.name'))->success();

        $text = $this->payload['subject']."\n".implode("\n", $this->payload['lines']);

        $msg->content($text);

        return $msg;
    }
}
