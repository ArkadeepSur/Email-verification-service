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
        [$local, $domain] = explode('@', $email);

        $records = dns_get_record($domain, DNS_MX);
        if (empty($records)) {
            return [
                'smtp' => 'invalid',
                'reason' => 'No MX records found',
            ];
        }

        usort($records, fn ($a, $b) => $a['pri'] <=> $b['pri']);

        foreach ($records as $mx) {
            $host = $mx['target'];
            $errno = 0;
            $errstr = '';

            try {
                $socket = fsockopen($host, 25, $errno, $errstr, 10);
                if (! $socket) {
                    continue;
                }

                stream_set_timeout($socket, 10);

                $this->read($socket); // banner

                $this->write($socket, 'EHLO verifier.local');
                $this->read($socket);

                $this->write($socket, 'MAIL FROM:<verify@verifier.local>');
                $this->read($socket);

                $this->write($socket, "RCPT TO:<{$email}>");
                $response = $this->read($socket);

                $this->write($socket, 'QUIT');
                fclose($socket);

                if (str_starts_with($response, '250')) {
                    return [
                        'smtp' => 'valid',
                        'reason' => 'Mailbox accepted',
                    ];
                }

                if (str_starts_with($response, '550')) {
                    return [
                        'smtp' => 'invalid',
                        'reason' => 'Mailbox rejected',
                    ];
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return [
            'smtp' => 'unknown',
            'reason' => 'SMTP verification inconclusive',
        ];
    }

    public function detectCatchAll(string $email, array $mxRecords): bool
    {
        [$user, $domain] = explode('@', $email, 2);

        $result = $this->catchAllDetector->detect($domain, $mxRecords);
        return $result['is_catch_all'] ?? false;
    }

    public function checkBlacklist(string $email): bool
    {
        return \App\Models\Blacklist::where('pattern', $email)->exists();
    }

    public function calculateRiskScore(array $data): int
    {
        // Start with maximum score
        $score = 100;
        
        // Check SMTP result
        $smtpResult = $data['smtp'] ?? [];
        if (is_array($smtpResult)) {
            if (!($smtpResult['ok'] ?? true)) {
                $score -= 50;
            }
        } elseif ($smtpResult !== 'valid') {
            $score -= 50;
        }
        
        // Check catch-all
        $catchAll = $data['catch_all'] ?? false;
        if (is_array($catchAll)) {
            if ($catchAll['is_catch_all'] ?? false) {
                $score -= 20;
            }
        } elseif ($catchAll === true) {
            $score -= 20;
        }
        
        // Check blacklist (most severe)
        if ($data['blacklist'] ?? false) {
            $score = 0; // Blacklisted = not valid
        }
        
        // Check disposable
        if ($data['disposable'] ?? false) {
            $score -= 50;
        }

        return max(0, $score);
    }

    public function isDisposable(string $email): bool
    {
        // Placeholder: check known disposable domains or use a package
        return false;
    }

    private function write($socket, string $command): void
    {
        fwrite($socket, $command."\r\n");
    }

    private function read($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (preg_match('/^\d{3}\s/', $line)) {
                break;
            }
        }

        return trim($response);
    }
}
