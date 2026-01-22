<?php

namespace App\Services;

use App\Jobs\VerifyBulkEmailsJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HubSpotService
{
    private const MAX_RETRIES = 3;

    private const RETRY_DELAY = 2; // seconds

    public function syncContacts(array $filters = [], ?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
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
                    'max_retries' => self::MAX_RETRIES,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    sleep(self::RETRY_DELAY * pow(2, $attempt - 1));
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
        throw new \RuntimeException('HubSpot client is not configured');
    }
}
