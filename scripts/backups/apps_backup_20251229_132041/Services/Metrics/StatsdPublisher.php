<?php

namespace App\Services\Metrics;

class StatsdPublisher implements MetricsPublisher
{
    protected string $host;
    protected int $port;
    protected string $prefix;

    public function __construct(string $host, int $port, string $prefix = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
    }

    protected function send(string $payload): void
    {
        try {
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_sendto($sock, $payload, strlen($payload), 0, $this->host, $this->port);
            socket_close($sock);
        } catch (\Throwable $e) {
            // silently ignore; metrics should not break the app
        }
    }

    public function increment(string $metric, int $by = 1): void
    {
        $msg = sprintf("%s%s:%d|c", $this->prefix, $metric, $by);
        $this->send($msg);
    }

    public function gauge(string $metric, $value): void
    {
        $msg = sprintf("%s%s:%s|g", $this->prefix, $metric, (string) $value);
        $this->send($msg);
    }
}
