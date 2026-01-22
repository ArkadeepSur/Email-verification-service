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
     * Validate webhook URL for security, including DNS resolution checks.
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
            } else {
                // It's a domain name, resolve it and validate the resolved IPs
                if (! $this->validateResolvedIPs($host)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate that a domain name resolves only to public IP addresses.
     */
    private function validateResolvedIPs(string $hostname): bool
    {
        try {
            // Try dns_get_record first for A and AAAA records
            $aRecords = @dns_get_record($hostname, DNS_A + DNS_AAAA);

            if (empty($aRecords)) {
                // Fallback to gethostbynamel
                $ips = @gethostbynamel($hostname);
                if (empty($ips) || ! is_array($ips)) {
                    Log::warning('DNS resolution failed for webhook URL', ['hostname' => $hostname]);

                    return false;
                }
            } else {
                // Extract IPs from dns_get_record results
                $ips = array_map(fn ($record) => $record['ip'] ?? null, $aRecords);
                $ips = array_filter($ips);
            }

            // Limit number of resolved addresses to prevent DOS
            if (count($ips) > 10) {
                Log::warning('DNS resolution returned too many addresses', ['hostname' => $hostname, 'count' => count($ips)]);

                return false;
            }

            // Validate each resolved IP
            foreach ($ips as $ip) {
                if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    Log::warning('Resolved IP is private or reserved', ['hostname' => $hostname, 'ip' => $ip]);

                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Error during DNS resolution for webhook URL', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
