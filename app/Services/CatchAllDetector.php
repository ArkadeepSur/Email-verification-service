<?php

namespace App\Services;

class CatchAllDetector
{
    public function detect(string $domain, array $mxRecords): array
    {
        // Generate random email addresses
        $testEmails = [
            $this->generateRandomEmail($domain),
            $this->generateRandomEmail($domain),
            $this->generateRandomEmail($domain),
        ];

        $acceptCount = 0;

        foreach ($testEmails as $testEmail) {
            if ($this->smtpAccepts($testEmail, $mxRecords)) {
                $acceptCount++;
            }
        }

        // If 2+ random emails accepted, likely catch-all
        $isCatchAll = $acceptCount >= 2;
        $confidence = ($acceptCount / count($testEmails)) * 100;

        return [
            'is_catch_all' => $isCatchAll,
            'confidence' => $confidence,
            'test_results' => $acceptCount,
        ];
    }

    private function smtpAccepts(string $email, array $mxRecords): bool
    {
        [$local, $domain] = explode('@', $email);

        if (empty($domain)) {
            return false;
        }
        $records = dns_get_record($domain, DNS_MX);
        if (empty($records)) {
            return false;
        }

        usort($records, fn ($a, $b) => $a['pri'] <=> $b['pri']);

        $fakeEmail = 'nonexistent_'.bin2hex(random_bytes(6)).'@'.$domain;

        foreach ($records as $mx) {
            try {
                $socket = fsockopen($mx['target'], 25, $errno, $errstr, 10);
                if (! $socket) {
                    continue;
                }

                stream_set_timeout($socket, 10);

                $this->read($socket);

                $this->write($socket, 'EHLO catchall.test');
                $this->read($socket);

                $this->write($socket, 'MAIL FROM:<check@verifier.local>');
                $this->read($socket);

                $this->write($socket, "RCPT TO:<{$fakeEmail}>");
                $response = $this->read($socket);

                $this->write($socket, 'QUIT');
                fclose($socket);

                if (str_starts_with($response, '250')) {
                    return true; // catch-all detected
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return false;
    }

    private function generateRandomEmail(string $domain): string
    {
        return bin2hex(random_bytes(5)).'@'.$domain;
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

    private function write($socket, string $command): void
    {
        fwrite($socket, $command."\r\n");
    }
}
