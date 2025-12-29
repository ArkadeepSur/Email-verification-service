<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\VerifyEmailJob;
use Mockery;
use App\Services\EmailVerificationService;

class VerifyEmailJobTest extends TestCase
{
    public function test_handle_calls_service_methods()
    {
        $email = 'user@example.com';

        $mock = Mockery::mock(EmailVerificationService::class);
        $mock->shouldReceive('precheck')->once()->with($email)->andReturn(['ok' => true]);
        $mock->shouldReceive('validateSyntax')->once()->with($email)->andReturn(true);
        $mock->shouldReceive('checkMXRecords')->once()->with($email)->andReturn(['mx1.example.com']);
        $mock->shouldReceive('verifySMTP')->once()->with($email, ['mx1.example.com'])->andReturn(['ok' => true]);
        $mock->shouldReceive('detectCatchAll')->once()->with($email, ['mx1.example.com'])->andReturn(['is_catch_all' => false]);
        $mock->shouldReceive('checkBlacklist')->once()->with($email)->andReturn(false);
        $mock->shouldReceive('isDisposable')->once()->with($email)->andReturn(false);
        $mock->shouldReceive('calculateRiskScore')->once()->andReturn(100);

        $this->app->instance(EmailVerificationService::class, $mock);

        $job = new VerifyEmailJob($email);
        // invoke handle directly (the container will inject the mocked service)
        $job->handle($mock);

        Mockery::close();

        $this->assertTrue(true); // if we reach here, interactions succeeded
    }
}
