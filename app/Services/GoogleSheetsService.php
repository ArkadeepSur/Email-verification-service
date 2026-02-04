<?php

namespace App\Services;

use App\Config\RetryConfig;
use App\Jobs\VerifyBulkEmailsJob;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    public function importEmails(string $spreadsheetId, string $range, int $userId)
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }

        // Validate retry configuration
        if (RetryConfig::MAX_RETRIES <= 0) {
            throw new \RuntimeException('Google Sheets import failed: no attempts configured');
        }

        $attempt = 0;

        while ($attempt < RetryConfig::MAX_RETRIES) {
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
                    'max_retries' => RetryConfig::MAX_RETRIES,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < RetryConfig::MAX_RETRIES) {
                    sleep(RetryConfig::getBackoffDelay($attempt));
                } else {
                    Log::error('Google Sheets import failed after max retries', [
                        'spreadsheet_id' => $spreadsheetId,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }

        // If we exit the loop without returning, throw exception
        throw new \RuntimeException('Google Sheets import failed after all retry attempts');
    }

    public function exportResults(string $spreadsheetId, array $results)
    {
        try {
            Log::info('Google Sheets export started', [
                'spreadsheet_id' => $spreadsheetId,
                'result_count' => count($results),
            ]);

            if (empty($results)) {
                Log::info('Google Sheets export completed (no results)', [
                    'spreadsheet_id' => $spreadsheetId,
                ]);

                return true;
            }

            $client = $this->getGoogleClient();

            if (class_exists('\Google_Service_Sheets')) {
                // Validate Google Sheets client is available before using real client
                if (! class_exists('\Google_Service_Sheets_ValueRange')) {
                    Log::warning('Google Sheets ValueRange class not available, skipping export', [
                        'spreadsheet_id' => $spreadsheetId,
                    ]);
                    throw new \RuntimeException('Google Sheets client (google/apiclient) is not installed');
                }

                $service = new \Google_Service_Sheets($client);
            } elseif (is_object($client) && property_exists($client, 'spreadsheets_values')) {
                // Mock client path - skip class validation
                $service = $client;
            } else {
                throw new \RuntimeException('Google Sheets client is not available');
            }

            // Transform results into sheet row format
            $rows = [['Email', 'Status', 'Risk Score', 'SMTP', 'Catch All', 'Disposable']];
            foreach ($results as $result) {
                $rows[] = [
                    $result['email'] ?? '',
                    $result['status'] ?? '',
                    $result['risk_score'] ?? 0,
                    $result['smtp'] ?? '',
                    ($result['catch_all'] ?? false) ? 'Yes' : 'No',
                    ($result['disposable'] ?? false) ? 'Yes' : 'No',
                ];
            }

            /** @var object $body */
            $body = new \Google_Service_Sheets_ValueRange;
            $body->setValues($rows);

            $attempt = 0;
            while ($attempt < RetryConfig::MAX_RETRIES) {
                try {
                    $service->spreadsheets_values->update(
                        $spreadsheetId,
                        'Results!A1',
                        $body,
                        ['valueInputOption' => 'RAW']
                    );

                    Log::info('Google Sheets export completed', [
                        'spreadsheet_id' => $spreadsheetId,
                        'rows_written' => count($rows),
                    ]);

                    return true;
                } catch (\Throwable $e) {
                    $attempt++;
                    Log::warning('Google Sheets export attempt failed', [
                        'spreadsheet_id' => $spreadsheetId,
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    if ($attempt < RetryConfig::MAX_RETRIES) {
                        sleep(RetryConfig::getBackoffDelay($attempt));
                    } else {
                        throw $e;
                    }
                }
            }

            // If we exit the loop without returning, throw exception
            throw new \RuntimeException('Google Sheets export failed after all retry attempts');
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
        // Lazily create a Google API client if the google/apiclient package is installed
        if (! class_exists('\Google_Client')) {
            throw new \RuntimeException('Google client library (google/apiclient) is not installed');
        }

        $jsonEnv = env('GOOGLE_SHEETS_CREDENTIALS_JSON');
        $pathEnv = env('GOOGLE_APPLICATION_CREDENTIALS');

        $client = new \Google_Client;

        if ($jsonEnv) {
            $decoded = json_decode($jsonEnv, true);
            if (! is_array($decoded)) {
                throw new \RuntimeException('Invalid JSON provided in GOOGLE_SHEETS_CREDENTIALS_JSON');
            }
            $client->setAuthConfig($decoded);
        } elseif ($pathEnv) {
            $path = base_path($pathEnv);
            if (! file_exists($path)) {
                throw new \RuntimeException('GOOGLE_APPLICATION_CREDENTIALS file not found: '.$pathEnv);
            }
            $client->setAuthConfig($path);
        } else {
            throw new \RuntimeException('Google Sheets credentials not configured. Set GOOGLE_SHEETS_CREDENTIALS_JSON or GOOGLE_APPLICATION_CREDENTIALS');
        }

        $client->addScope(\Google_Service_Sheets::SPREADSHEETS);
        $client->setApplicationName(config('app.name', 'email-verification-service'));

        return $client;
    }
}
