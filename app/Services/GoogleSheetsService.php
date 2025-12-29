<?php

namespace App\Services;

use App\Jobs\VerifyBulkEmailsJob;
use Google_Service_Sheets;

class GoogleSheetsService
{
    public function importEmails(string $spreadsheetId, string $range)
    {
        $client = $this->getGoogleClient();
        $service = new Google_Service_Sheets($client);

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
}
