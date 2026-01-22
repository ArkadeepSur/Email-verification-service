<?php

namespace App\Jobs;

use App\Models\VerificationResult;
use App\Services\EmailVerificationService;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $userId;

    public string $email;

    public function __construct(?int $userId, string $email)
    {
        $this->userId = $userId;
        $this->email = $email;
    }

    public function handle(): void
    {
        $service = app(EmailVerificationService::class);

        $service->precheck($this->email);

        if (! $service->validateSyntax($this->email)) {
            $this->markInvalid('syntax_error');

            return;
        }

        $mxRecords = $service->checkMXRecords($this->email);
        if (empty($mxRecords)) {
            $this->markInvalid('no_mx_records');

            return;
        }

        $smtpResult = $service->verifySMTP($this->email, $mxRecords);
        $isCatchAll = $service->detectCatchAll($this->email, $mxRecords);
        $isBlacklisted = $service->checkBlacklist($this->email);

        $isDisposable = $service->isDisposable($this->email);
        $riskScore = $service->calculateRiskScore([
            'smtp' => $smtpResult,
            'catch_all' => $isCatchAll,
            'blacklist' => $isBlacklisted,
            'disposable' => $isDisposable,
        ]);

        $this->saveResult($riskScore, $smtpResult, $isCatchAll, $isDisposable);
    }

    private function markInvalid(string $reason): void
    {
        VerificationResult::create([
            'user_id' => $this->userId,
            'email' => $this->email,
            'syntax_valid' => false,
            'smtp' => 'unknown',
            'catch_all' => false,
            'disposable' => false,
            'risk_score' => 0,
            'status' => 'invalid',
            'details' => ['reason' => $reason],
        ]);
    }

    private function saveResult(int $riskScore, array $smtpResult, bool $isCatchAll, bool $isDisposable): void
    {
        $result = VerificationResult::create([
            'user_id' => $this->userId,
            'email' => $this->email,
            'syntax_valid' => true,
            'status' => $riskScore > 0 ? 'valid' : 'invalid',
            'risk_score' => $riskScore,
            'smtp' => $smtpResult['smtp'] ?? 'unknown',
            'catch_all' => $isCatchAll,
            'disposable' => $isDisposable,
            'details' => [
                'smtp' => $smtpResult,
                'catch_all' => $isCatchAll,
            ],
        ]);

        try {
            app(WebhookService::class)->trigger('verification.completed', [
                'id' => $result->id,
                'email' => $this->email,
                'status' => $result->status,
                'risk_score' => $riskScore,
            ]);
        } catch (\Throwable) {
            // swallow webhook failures
        }
    }
}
