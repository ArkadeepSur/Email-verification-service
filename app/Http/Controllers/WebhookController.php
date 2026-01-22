<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'event' => ['required', 'string', 'in:verification.completed,verification.failed,credits.low,subscription.renewed'],
            'secret' => ['nullable', 'string', 'min:12', 'max:255'],
        ]);

        // Additional URL validation: ensure it's not localhost (unless in testing)
        if (! $this->isValidWebhookUrl($data['url'])) {
            return response()->json(['error' => 'Invalid webhook URL. Must be publicly accessible.'], 422);
        }

        try {
            $webhook = Webhook::create(array_merge($data, [
                'is_active' => true,
                'user_id' => auth()->id(),
            ]));

            Log::info('Webhook registered', [
                'webhook_id' => $webhook->id,
                'user_id' => $webhook->user_id,
                'event' => $webhook->event,
            ]);

            return response()->json($webhook, 201);
        } catch (\Throwable $e) {
            Log::error('Webhook registration failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to register webhook'], 500);
        }
    }

    /**
     * Validate webhook URL for security.
     */
    private function isValidWebhookUrl(string $url): bool
    {
        $parsed = parse_url($url);

        // Only allow HTTPS in production
        if (config('app.env') === 'production' && ($parsed['scheme'] ?? null) !== 'https') {
            return false;
        }

        // Reject localhost and internal IPs unless in testing
        if (config('app.env') !== 'testing') {
            $host = $parsed['host'] ?? null;
            if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return false;
            }

            // Check for private IP ranges only if $host is actually an IP address
            $ipValidation = filter_var($host, FILTER_VALIDATE_IP);
            if ($ipValidation !== false) {
                // It's an IP address, check if it's private/reserved
                if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    return false;
                }
            }
            // If it's a domain name (not an IP), allow it to continue
        }

        return true;
    }
}
