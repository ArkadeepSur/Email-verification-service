<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use App\Jobs\VerifyEmailJob;
use App\Models\User;
use App\Models\VerificationResult;

class VerificationPipelineTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_email_verification_fully()
    {
        // Fake queues and HTTP calls
        Queue::fake();
        Http::fake();

        // Create a user
        $user = User::factory()->create();

        $email = 'test@example.com';

        // Dispatch verification job (simulating pipeline)
        VerifyEmailJob::dispatch($user->id, $email);

        // Assert job is pushed
        Queue::assertPushed(VerifyEmailJob::class, function ($job) use ($user, $email) {
            return $job->userId === $user->id && $job->email === $email;
        });

        // Run the job synchronously
        $job = new VerifyEmailJob($user->id, $email);
        $job->handle();

        // Assert DB record exists
        $this->assertDatabaseHas('verification_results', [
            'user_id' => $user->id,
            'email' => $email
        ]);

        $result = VerificationResult::where('email', $email)->first();

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

        $this->assertEquals('invalid', $result->syntax_valid);
        $this->assertEquals('unknown', $result->smtp);
        $this->assertEquals(false, $result->catch_all);
    }
}
