<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Webhook;

class WebhookService
{
    public function trigger(string $event, array $payload)
    {
        $webhooks = Webhook::where('event', $event)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            dispatch(new SendWebhookJob($webhook->url, $payload, $webhook->secret));
        }
    }
}

// Webhook Events:
// - verification.completed
// - verification.failed
// - credits.low
// - subscription.renewed
