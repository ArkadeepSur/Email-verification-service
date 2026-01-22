<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyBulkEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $emails;

    public ?int $userId;

    public function __construct(?int $userId, array $emails)
    {
        $this->userId = $userId;
        $this->emails = $emails;
    }

    public function handle()
    {
        foreach ($this->emails as $email) {
            VerifyEmailJob::dispatch($this->userId, $email);
        }
    }
}
