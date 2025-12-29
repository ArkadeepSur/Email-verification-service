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

    public function __construct(array $emails)
    {
        $this->emails = $emails;
    }

    public function handle()
    {
        foreach ($this->emails as $email) {
            VerifyEmailJob::dispatch($email);
        }
    }
}
