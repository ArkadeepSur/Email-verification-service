<?php

namespace App\Config;

class RetryConfig
{
    public const MAX_RETRIES = 3;

    public const RETRY_DELAY = 2; // seconds

    /**
     * Calculate backoff delay for a given attempt.
     *
     * @param  int  $attempt  (1-indexed)
     * @return int Delay in seconds
     */
    public static function getBackoffDelay(int $attempt): int
    {
        return self::RETRY_DELAY * pow(2, $attempt - 1);
    }
}
