<?php

namespace Tests\Unit;

use App\Jobs\VerifyEmailJob;
use App\Models\User;
use App\Models\VerificationResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VerificationPipelineTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_email_verification_fully()
    {
        // Fake events, queues and HTTP calls
        Event::fake();
        Queue::fake();
        Http::fake();

        // Create a user
        $user = User::factory()->create();

        $email = 'test@example.com';

        // Mock EmailVerificationService to avoid network calls
        $serviceMock = \Mockery::mock(\App\Services\EmailVerificationService::class);
        $serviceMock
            ->shouldReceive('precheck')
            ->with($email)
            ->andReturn(['ok' => true]);
        $serviceMock
            ->shouldReceive('validateSyntax')
            ->with($email)
            ->andReturn(true);
        $serviceMock
            ->shouldReceive('checkMXRecords')
            ->with($email)
            ->andReturn([['target' => 'mail.example.com', 'pri' => 10]]);
        $serviceMock
            ->shouldReceive('verifySMTP')
            ->andReturn(['smtp' => 'valid', 'reason' => 'Mailbox accepted']);
        $serviceMock
            ->shouldReceive('detectCatchAll')
            ->andReturn(false);
        $serviceMock
            ->shouldReceive('checkBlacklist')
            ->andReturn(false);
        $serviceMock
            ->shouldReceive('isDisposable')
            ->andReturn(false);
        $serviceMock
            ->shouldReceive('calculateRiskScore')
            ->andReturn(5);

        $this->app->instance(\App\Services\EmailVerificationService::class, $serviceMock);

        // Dispatch verification job (simulating pipeline)
        VerifyEmailJob::dispatch($user->id, $email);

        // Assert job is pushed
        Queue::assertPushed(VerifyEmailJob::class, function ($job) use ($user, $email) {
            return $job->userId === $user->id && $job->email === $email;
        });

        // Run the job synchronously
        $job = new VerifyEmailJob($user->id, $email);
        $job->handle();

        // Assert DB record exists with user_id
        $this->assertDatabaseHas('verification_results', [
            'user_id' => $user->id,
            'email' => $email,
        ]);

        $result = VerificationResult::where('email', $email)->first();

        // Check details array for verification flags
        $this->assertIsArray($result->details);
        $this->assertNotNull($result->syntax_valid);
        $this->assertNotNull($result->smtp);
        $this->assertNotNull($result->catch_all);
        $this->assertNotNull($result->disposable);

        // Optionally check webhook dispatch (mocked)
        Http::assertNothingSent(); // no real webhook call

        // Optional: check metrics (if using MetricsPublisher)
        Event::fake();
    }

    /** @test */
    public function it_handles_invalid_email_gracefully()
    {
        $user = User::factory()->create();
        $email = 'invalid-email';

        $job = new VerifyEmailJob($user->id, $email);
        $job->handle();

        $result = VerificationResult::where('email', $email)->first();

        $this->assertFalse($result->syntax_valid);
        $this->assertEquals('unknown', $result->smtp);
        $this->assertFalse($result->catch_all);
    }
}
