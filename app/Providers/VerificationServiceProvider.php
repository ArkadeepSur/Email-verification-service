<?php

namespace App\Providers;

use App\Services\CatchAllDetector;
use App\Services\EmailVerificationService;
use Illuminate\Support\ServiceProvider;

class VerificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(EmailVerificationService::class, function ($app) {
            return new EmailVerificationService(new CatchAllDetector);
        });
    }

    public function boot()
    {
        // bootstrapping if necessary
    }
}
