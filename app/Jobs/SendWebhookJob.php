<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;

    public $event;

    public $payload;

    public $tries = 5; // maximum retries

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $event, array $payload)
    {
        $this->userId = $userId;
        $this->event = $event;
        $this->payload = $payload;
    }

    /**
     * Exponential backoff for retries.
     */
    public function backoff(): array
    {
        $attempts = $this->attempts();

        return pow(2, $attempts) * 10; // exponential seconds
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // fetch webhook URL from your database
        $webhook = \App\Models\Webhook::where('user_id', $this->userId)
            ->where('event', $this->event)
            ->first();

        if (! $webhook) {
            // no webhook to send
            return;
        }

        $response = Http::timeout(5)->post($webhook->url, $this->payload);

        if (! $response->successful()) {
            // Throw exception to retry automatically
            throw new \Exception('Webhook failed: HTTP '.$response->status());
        }
    }

    /**
     * Optional: log failed jobs permanently
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Webhook failed permanently', [
            'user_id' => $this->userId,
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);
    }
}
