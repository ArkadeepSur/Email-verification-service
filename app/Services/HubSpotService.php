<?php

namespace App\Services;

use App\Jobs\VerifyBulkEmailsJob;
use Illuminate\Support\Facades\Auth;

class HubSpotService
{
    public function syncContacts(array $filters = [], ?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $client = $this->getHubSpotClient();

        // Fetch contacts from HubSpot
        $contacts = $client->crm()->contacts()->getAll();

        // Extract emails and verify
        $emails = collect($contacts)->pluck('properties.email')->filter();

        VerifyBulkEmailsJob::dispatch($userId, $emails->toArray());

        return true;
    }

    public function updateContactProperties(array $verificationResults)
    {
        // Update HubSpot contact properties with verification status
        $client = $this->getHubSpotClient();

        foreach ($verificationResults as $result) {
            $client->crm()->contacts()->update($result['contact_id'], [
                'email_verified' => $result['status'],
                'email_risk_score' => $result['risk_score'],
                'last_verified_at' => now(),
            ]);
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
