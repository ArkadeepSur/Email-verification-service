<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthEmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_redirected_to_verification_notice(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/email/verify');
    }

    public function test_verified_user_can_access_dashboard(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }

    public function test_unverified_user_can_view_verification_notice(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertViewIs('auth.verify-email');
    }

    public function test_guest_redirected_from_verification_notice(): void
    {
        $response = $this->get('/email/verify');

        $response->assertRedirect('/login');
    }

    public function test_verification_email_can_be_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)
            ->from('/email/verify')
            ->post('/email/verification-notification');

        $response->assertRedirect('/email/verify');
        $response->assertSessionHas('message', 'Verification link sent!');
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
