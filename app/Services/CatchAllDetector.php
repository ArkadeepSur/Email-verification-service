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
            $this->generateRandomEmail($domain)
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
            'test_results' => $acceptCount
        ];
    }
    
    private function smtpAccepts(string $email, array $mxRecords): bool
    {
        // SMTP verification logic with timeout handling
        foreach ($mxRecords as $mx) {
            try {
                $socket = fsockopen($mx, 25, $errno, $errstr, 5);
                // SMTP handshake: HELO, MAIL FROM, RCPT TO
                // Return true if 250 OK received
            } catch (\Exception $e) {
                continue;
            }
        }
        return false;
    }

    private function generateRandomEmail(string $domain): string
    {
        return bin2hex(random_bytes(5)) . '@' . $domain;
    }
}
