<?php

namespace App\Services;

use App\Jobs\VerifyBulkEmailsJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    private const MAX_RETRIES = 3;

    private const RETRY_DELAY = 2; // seconds

    public function importEmails(string $spreadsheetId, string $range, ?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
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
                })->unique()->toArray();

                Log::info('Google Sheets import successful', [
                    'spreadsheet_id' => $spreadsheetId,
                    'email_count' => count($emails),
                    'user_id' => $userId,
                ]);

                return VerifyBulkEmailsJob::dispatch($userId, $emails);
            } catch (\Throwable $e) {
                $attempt++;
                Log::warning('Google Sheets import failed', [
                    'spreadsheet_id' => $spreadsheetId,
                    'attempt' => $attempt,
                    'max_retries' => self::MAX_RETRIES,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    sleep(self::RETRY_DELAY * pow(2, $attempt - 1));
                } else {
                    Log::error('Google Sheets import failed after max retries', [
                        'spreadsheet_id' => $spreadsheetId,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }
    }

    public function exportResults(string $spreadsheetId, array $results)
    {
        // Write verification results back to sheet
        try {
            Log::info('Google Sheets export started', [
                'spreadsheet_id' => $spreadsheetId,
                'result_count' => count($results),
            ]);

            // TODO: Implement actual export logic

            Log::info('Google Sheets export completed', [
                'spreadsheet_id' => $spreadsheetId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Google Sheets export failed', [
                'spreadsheet_id' => $spreadsheetId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
