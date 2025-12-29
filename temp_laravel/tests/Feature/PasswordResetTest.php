<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;
use App\Models\User;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_sends_email_and_resets_password()
    {
        Notification::fake();

        $user = new User();
        $user->name = 'Reset User';
        $user->email = 'reset@example.com';
        $user->password = Hash::make('oldpassword');
        $user->save();

        // Request reset link
        $resp = $this->post(route('password.email'), ['email' => $user->email]);
        $resp->assertSessionHas('status');

        // Assert notification was sent
        Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channels) use ($user) {
            // Use the token from the notification to perform the reset
            $token = $notification->token;

            $resetResp = $this->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $resetResp->assertRedirect(route('dashboard'));
            $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

            return true;
        });
    }

    public function test_password_reset_validation_errors()
    {
        $resp = $this->post(route('password.update'), [
            'token' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'no-match',
        ]);

        $resp->assertSessionHasErrors(['token', 'email', 'password']);
    }
}
