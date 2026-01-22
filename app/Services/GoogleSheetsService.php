<?php

namespace App\Services;

use App\Jobs\VerifyBulkEmailsJob;

class GoogleSheetsService
{
    public function importEmails(string $spreadsheetId, string $range)
    {
        $client = $this->getGoogleClient();

        // If the real Google API client class is available, prefer to wrap it.
        if (class_exists('\Google_Service_Sheets')) {
            $service = new \Google_Service_Sheets($client);
        } elseif (is_object($client) && property_exists($client, 'spreadsheets_values')) {
            // tests may provide a fake 'service' object directly with spreadsheets_values
            $service = $client;
        } else {
            throw new \RuntimeException('Google Sheets client is not available');
        }

        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        // Extract emails and queue verification
        $emails = collect($values)->flatten()->filter(function ($value) {
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        });

        return VerifyBulkEmailsJob::dispatch($emails->toArray());
    }

    public function exportResults(string $spreadsheetId, array $results)
    {
        // Write verification results back to sheet
    }

    /**
     * Return a configured Google client instance or throw if not configured.
     * Tests may stub this method.
     *
     * @return mixed
     */
    protected function getGoogleClient()
    {
        throw new \RuntimeException('Google client is not configured');
    }
}

