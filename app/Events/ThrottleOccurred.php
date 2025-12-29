<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class ThrottleOccurred
{
    use Dispatchable, SerializesModels;

    public string $key;
    public string $email;
    public string $ip;

    public function __construct(string $key, string $email, string $ip)
    {
        $this->key = $key;
        $this->email = $email;
        $this->ip = $ip;
    }
}
