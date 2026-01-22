<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Send hourly throttle digests to admins
        $schedule->command('throttle:send-digest --window=hour')->hourly();

        // Send daily digest at 01:00
        $schedule->command('throttle:send-digest --window=day')->dailyAt('01:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}

