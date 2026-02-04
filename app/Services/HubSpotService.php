<?php

namespace App\Services;

use App\Config\RetryConfig;
use App\Jobs\VerifyBulkEmailsJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HubSpotService
{
    public function syncContacts(array $filters = [], ?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $attempt = 0;

        while ($attempt < RetryConfig::MAX_RETRIES) {
            try {
                $client = $this->getHubSpotClient();

                // Fetch contacts from HubSpot
                $contacts = $client->crm()->contacts()->getAll();

                // Extract emails and verify
                $emails = collect($contacts)
                    ->pluck('properties.email')
                    ->filter(function ($email) {
                        return filter_var($email, FILTER_VALIDATE_EMAIL);
                    })
                    ->unique()
                    ->toArray();

                Log::info('HubSpot sync successful', [
                    'email_count' => count($emails),
                    'user_id' => $userId,
                ]);

                return VerifyBulkEmailsJob::dispatch($userId, $emails);
            } catch (\Throwable $e) {
                $attempt++;
                Log::warning('HubSpot sync failed', [
                    'attempt' => $attempt,
                    'max_retries' => RetryConfig::MAX_RETRIES,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < RetryConfig::MAX_RETRIES) {
                    sleep(RetryConfig::getBackoffDelay($attempt));
                } else {
                    Log::error('HubSpot sync failed after max retries', [
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }
    }

    public function updateContactProperties(array $verificationResults)
    {
        try {
            $client = $this->getHubSpotClient();
            $updated = 0;
            $failed = 0;

            foreach ($verificationResults as $result) {
                try {
                    $client->crm()->contacts()->update($result['contact_id'], [
                        'email_verified' => $result['status'],
                        'email_risk_score' => $result['risk_score'],
                        'last_verified_at' => now(),
                    ]);
                    $updated++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Failed to update HubSpot contact', [
                        'contact_id' => $result['contact_id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('HubSpot property update completed', [
                'updated' => $updated,
                'failed' => $failed,
                'total' => count($verificationResults),
            ]);

            return $updated > 0;
        } catch (\Throwable $e) {
            Log::error('HubSpot property update failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Return a configured HubSpot client instance or throw if not configured.
     * Tests may stub this method.
     *
     * @return mixed
     */
    protected function getHubSpotClient()
    {
        // Create a HubSpot client if the hubspot/api-client package is available
        if (class_exists('\HubSpot\Factory')) {
            $accessToken = env('HUBSPOT_ACCESS_TOKEN');
            $apiKey = env('HUBSPOT_API_KEY');

            if ($accessToken) {
                return \HubSpot\Factory::createWithAccessToken($accessToken);
            }

            if ($apiKey) {
                return \HubSpot\Factory::createWithApiKey($apiKey);
            }

            throw new \RuntimeException('HubSpot credentials not configured. Set HUBSPOT_ACCESS_TOKEN or HUBSPOT_API_KEY');
        }

        throw new \RuntimeException('HubSpot client library (hubspot/api-client) is not installed');
    }
}
