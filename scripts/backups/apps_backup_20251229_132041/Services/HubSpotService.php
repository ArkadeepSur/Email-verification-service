<?php

namespace App\Services;

use App\Jobs\VerifyBulkEmailsJob;

class HubSpotService
{
    public function syncContacts(array $filters = [])
    {
        $client = $this->getHubSpotClient();
        
        // Fetch contacts from HubSpot
        $contacts = $client->crm()->contacts()->getAll();
        
        // Extract emails and verify
        $emails = collect($contacts)->pluck('properties.email')->filter();
        
        $job = VerifyBulkEmailsJob::dispatch($emails->toArray());
        
        return $job->id;
    }
    
    public function updateContactProperties(array $verificationResults)
    {
        // Update HubSpot contact properties with verification status
        foreach ($verificationResults as $result) {
            $this->client->crm()->contacts()->update($result['contact_id'], [
                'email_verified' => $result['status'],
                'email_risk_score' => $result['risk_score'],
                'last_verified_at' => now()
            ]);
        }
    }
}
