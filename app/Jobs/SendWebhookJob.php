<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): int
    {
        return (int) (60 * pow(2, $this->attempts() - 1));
    }

    public function __construct(
        public string $url,
        public array $payload,
        public ?string $secret = null
    ) {}

    public function handle(): void
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Email-Verification-Service/1.0',
            ];

            if ($this->secret) {
                $payload = json_encode($this->payload);
                $signature = hash_hmac('sha256', $payload, $this->secret);
                $headers['X-Webhook-Signature'] = $signature;
            }

            $response = Http::timeout(10)
                ->retry(0)
                ->post($this->url, $this->payload);

            if (! $response->successful()) {
                Log::warning('Webhook delivery failed', [
                    'url' => $this->url,
                    'status' => $response->status(),
                    'attempt' => $this->attempts(),
                    'body' => $response->body(),
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release((int) $this->backoff());
                } else {
                    Log::error('Webhook delivery failed after max retries', [
                        'url' => $this->url,
                        'attempts' => $this->attempts(),
                    ]);
                }
            } else {
                Log::info('Webhook delivered successfully', [
                    'url' => $this->url,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Webhook delivery error', [
                'url' => $this->url,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release((int) $this->backoff());
            } else {
                Log::error('Webhook delivery failed after max retries with exception', [
                    'url' => $this->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
