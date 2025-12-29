<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\VerifyEmailJob;
use Mockery;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\VerificationResult;

class VerifyEmailJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_persists_result()
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
        $job->handle($mock);

        $this->assertDatabaseHas('verification_results', ['email' => $email, 'risk_score' => 100]);

        Mockery::close();
    }
}
