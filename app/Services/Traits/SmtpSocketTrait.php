<?php

namespace App\Services\Traits;

trait SmtpSocketTrait
{
    /**
     * Read response from SMTP socket.
     */
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

    /**
     * Write command to SMTP socket.
     */
    private function write($socket, string $command): void
    {
        fwrite($socket, $command."\r\n");
    }
}
