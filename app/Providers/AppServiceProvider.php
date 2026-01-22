<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Auto-register verification services
        $this->app->register(VerificationServiceProvider::class);
        // Metrics publisher binding
        $this->app->singleton(\App\Services\Metrics\MetricsPublisher::class, function ($app) {
            $driver = config('metrics.driver', 'null');
            if ($driver === 'statsd') {
                $host = config('metrics.statsd.host');
                $port = config('metrics.statsd.port');
                $prefix = config('metrics.statsd.prefix', '');

                return new \App\Services\Metrics\StatsdPublisher($host, $port, $prefix);
            }

            return new \App\Services\Metrics\NullPublisher;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

