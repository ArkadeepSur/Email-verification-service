<?php

namespace App\Jobs;

use App\Services\EmailVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function handle(EmailVerificationService $service)
    {
        // 1. Precheck Logic
        $precheckResult = $service->precheck($this->email);

        // 2. Syntax Validation
        if (! $service->validateSyntax($this->email)) {
            return $this->markInvalid('syntax_error');
        }

        // 3. DNS/MX Record Check
        $mxRecords = $service->checkMXRecords($this->email);
        if (empty($mxRecords)) {
            return $this->markInvalid('no_mx_records');
        }

        // 4. SMTP Connection Test
        $smtpResult = $service->verifySMTP($this->email, $mxRecords);

        // 5. Catch-All Detection (Smart Logic)
        $isCatchAll = $service->detectCatchAll($this->email, $mxRecords);

        // 6. Blacklist Check
        $isBlacklisted = $service->checkBlacklist($this->email);

        // 7. Risk Score Calculation
        $riskScore = $service->calculateRiskScore([
            'smtp' => $smtpResult,
            'catch_all' => $isCatchAll,
            'blacklist' => $isBlacklisted,
            'disposable' => $service->isDisposable($this->email),
        ]);

        // 8. Save Result
        $this->saveResult($riskScore, $smtpResult, $isCatchAll);
    }

    private function markInvalid(string $reason)
    {
        $jobId = null;
        if (isset($this->job) && method_exists($this->job, 'getJobId')) {
            $jobId = $this->job->getJobId();
        }

        \App\Models\VerificationResult::create([
            'email' => $this->email,
            'status' => 'invalid',
            'risk_score' => 0,
            'details' => ['reason' => $reason],
            'job_id' => $jobId,
        ]);
    }

    private function saveResult($riskScore, $smtpResult, $isCatchAll)
    {
        $jobId = null;
        if (isset($this->job) && method_exists($this->job, 'getJobId')) {
            $jobId = $this->job->getJobId();
        }

        $details = [
            'smtp' => $smtpResult,
            'catch_all' => $isCatchAll,
        ];

        $result = \App\Models\VerificationResult::create([
            'email' => $this->email,
            'status' => $riskScore > 0 ? 'ok' : 'invalid',
            'risk_score' => $riskScore,
            'details' => $details,
            'job_id' => $jobId,
        ]);

        // Fire webhooks (don't let failures break the job)
        try {
            app(\App\Services\WebhookService::class)->trigger('verification.completed', [
                'email' => $this->email,
                'status' => $result->status,
                'risk_score' => $riskScore,
                'id' => $result->id,
            ]);
        } catch (\Throwable $ex) {
            // ignore webhook errors
        }
    }
}

