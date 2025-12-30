<?php

namespace Tests\Feature;

use App\Jobs\SendWebhookJob;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trigger_dispatches_send_webhook_jobs()
    {
        Bus::fake();

        Webhook::create([
            'url' => 'https://example.com/hook',
            'event' => 'verification.completed',
            'secret' => null,
            'is_active' => true,
        ]);

        app(\App\Services\WebhookService::class)->trigger('verification.completed', ['email' => 'user@example.com']);

        Bus::assertDispatched(SendWebhookJob::class, function ($job) {
            return $job->url === 'https://example.com/hook' && $job->payload['email'] === 'user@example.com';
        });
    }
}
