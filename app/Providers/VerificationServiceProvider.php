<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EmailVerificationService;
use App\Services\CatchAllDetector;

class VerificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(EmailVerificationService::class, function ($app) {
            return new EmailVerificationService(new CatchAllDetector());
        });
    }

    public function boot()
    {
        // bootstrapping if necessary
    }
}
