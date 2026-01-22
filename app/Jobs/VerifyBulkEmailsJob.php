<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyBulkEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CHUNK_SIZE = 100;

    private const DELAY_BETWEEN_CHUNKS = 5; // seconds

    public array $emails;

    public ?int $userId;

    public function __construct(?int $userId, array $emails)
    {
        $this->userId = $userId;
        $this->emails = $emails;
    }

    public function handle(): void
    {
        $chunks = array_chunk($this->emails, self::CHUNK_SIZE);
        $totalEmails = count($this->emails);
        $processedEmails = 0;

        Log::info('Starting bulk email verification', [
            'user_id' => $this->userId,
            'total_emails' => $totalEmails,
            'chunks' => count($chunks),
        ]);

        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $email) {
                VerifyEmailJob::dispatch($this->userId, $email);
                $processedEmails++;
            }

            // Add delay between chunks to avoid overwhelming SMTP servers
            if ($chunkIndex < count($chunks) - 1) {
                sleep(self::DELAY_BETWEEN_CHUNKS);
            }

            Log::debug('Bulk verification progress', [
                'processed' => $processedEmails,
                'total' => $totalEmails,
                'percentage' => round(($processedEmails / $totalEmails) * 100, 2),
            ]);
        }

        Log::info('Bulk email verification completed', [
            'user_id' => $this->userId,
            'total_emails' => $totalEmails,
        ]);
    }
}
