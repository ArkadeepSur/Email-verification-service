<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LockoutNotification extends Notification
{
    use Queueable;

    public string $ip;

    public int $minutes;

    public function __construct(string $ip, int $minutes)
    {
        $this->ip = $ip;
        $this->minutes = $minutes;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Account temporarily locked')
            ->line("We detected multiple failed login attempts for your account from IP: {$this->ip}.")
            ->line("The account has been temporarily locked for {$this->minutes} minute(s). If this wasn't you, please reset your password or contact support.")
            ->line('If you recognize these attempts, no action is necessary.');
    }
}

