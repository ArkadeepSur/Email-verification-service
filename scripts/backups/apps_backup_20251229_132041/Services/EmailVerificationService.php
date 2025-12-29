<?php

namespace App\Services;

class EmailVerificationService
{
    protected CatchAllDetector $catchAllDetector;

    public function __construct(CatchAllDetector $catchAllDetector)
    {
        $this->catchAllDetector = $catchAllDetector;
    }

    public function precheck(string $email): array
    {
        // Placeholder for DNS, disposable checks etc.
        return ['ok' => true];
    }

    public function validateSyntax(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function checkMXRecords(string $email): array
    {
        [$user, $domain] = explode('@', $email, 2);
        $mx = [];
        if (getmxrr($domain, $mx)) {
            return $mx;
        }

        return [];
    }

    public function verifySMTP(string $email, array $mxRecords): array
    {
        // Lightweight placeholder
        return ['ok' => true];
    }

    public function detectCatchAll(string $email, array $mxRecords): array
    {
        [$user, $domain] = explode('@', $email, 2);

        return $this->catchAllDetector->detect($domain, $mxRecords);
    }

    public function checkBlacklist(string $email): bool
    {
        return \App\Models\Blacklist::where('pattern', $email)->exists();
    }

    public function calculateRiskScore(array $data): int
    {
        // Simple scoring placeholder
        $score = 100;
        if (! $data['smtp']['ok']) {
            $score -= 50;
        }
        if ($data['catch_all']['is_catch_all']) {
            $score -= 20;
        }
        if ($data['blacklist']) {
            $score -= 100;
        }
        if ($data['disposable']) {
            $score -= 50;
        }

        return max(0, $score);
    }

    public function isDisposable(string $email): bool
    {
        // Placeholder: check known disposable domains or use a package
        return false;
    }
}
