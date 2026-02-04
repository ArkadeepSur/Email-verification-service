<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use App\Config\RetryConfig;
use App\Jobs\VerifyBulkEmailsJob;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected Sheets $sheets;

    public function __construct(?Sheets $sheets = null)
    {
        // ✅ Test path: injected mock
        if ($sheets !== null) {
            $this->sheets = $sheets;
            return;
        }

        // ✅ Runtime path: real Google client
        $client = $this->createGoogleClient();
        $this->sheets = new Sheets($client);
    }

    public function importEmails(string $spreadsheetId, string $range, int $userId)
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }

        if (RetryConfig::MAX_RETRIES <= 0) {
            throw new \RuntimeException('Google Sheets import failed: no attempts configured');
        }

        $attempt = 0;

        while ($attempt < RetryConfig::MAX_RETRIES) {
            try {
                // ✅ ALWAYS use injected Sheets service
                $response = $this->sheets
                    ->spreadsheets_values
                    ->get($spreadsheetId, $range);

                $values = $response->values ?? [];

                $emails = collect($values)
                    ->flatten()
                    ->filter(fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL))
                    ->unique()
                    ->values()
                    ->toArray();

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

                if ($attempt >= RetryConfig::MAX_RETRIES) {
                    throw $e;
                }

                sleep(RetryConfig::getBackoffDelay($attempt));
            }
        }

        throw new \RuntimeException('Google Sheets import failed after all retry attempts');
    }

    /**
     * Runtime-only Google client creation
     */
    protected function createGoogleClient(): Client
    {
        if (! class_exists(Client::class)) {
            throw new \RuntimeException('google/apiclient is not installed');
        }

        $jsonEnv = env('GOOGLE_SHEETS_CREDENTIALS_JSON');
        $pathEnv = env('GOOGLE_APPLICATION_CREDENTIALS');

        $client = new Client();

        if ($jsonEnv) {
            $decoded = json_decode($jsonEnv, true);
            if (! is_array($decoded)) {
                throw new \RuntimeException('Invalid JSON in GOOGLE_SHEETS_CREDENTIALS_JSON');
            }
            $client->setAuthConfig($decoded);
        } elseif ($pathEnv) {
            $path = base_path($pathEnv);
            if (! file_exists($path)) {
                throw new \RuntimeException("Credentials file not found: {$pathEnv}");
            }
            $client->setAuthConfig($path);
        } else {
            throw new \RuntimeException(
                'Google Sheets credentials not configured. Set GOOGLE_SHEETS_CREDENTIALS_JSON or GOOGLE_APPLICATION_CREDENTIALS'
            );
        }

        $client->addScope(Sheets::SPREADSHEETS);
        $client->setApplicationName(config('app.name', 'email-verification-service'));

        return $client;
    }
}
