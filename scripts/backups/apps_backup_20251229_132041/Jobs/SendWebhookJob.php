<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $url;
    public array $payload;
    public ?string $secret;

    public function __construct(string $url, array $payload, ?string $secret = null)
    {
        $this->url = $url;
        $this->payload = $payload;
        $this->secret = $secret;
    }

    public function handle()
    {
        $headers = ['Accept' => 'application/json'];
        if ($this->secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($this->payload), $this->secret);
        }

        Http::withHeaders($headers)->post($this->url, $this->payload);
    }
}
