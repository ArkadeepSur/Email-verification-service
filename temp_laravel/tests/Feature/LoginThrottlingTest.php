<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_throttles_after_multiple_failed_attempts()
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        // 5 failed attempts allowed (configured as throttle:5,1)
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            // Laravel redirects back with errors on failed attempt
            $resp->assertStatus(302);
            $resp->assertSessionHasErrors(['email']);
        }

        // 6th attempt should be throttled (429)
        $throttled = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $throttled->assertStatus(429);
    }

    public function test_successful_login_resets_throttle_counter()
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        // Make some failed attempts
        for ($i = 0; $i < 3; $i++) {
            $resp = $this->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            $resp->assertStatus(302);
            $resp->assertSessionHasErrors(['email']);
        }

        // Successful login should reset the throttle counter
        $login = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);
        $login->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Logout to clear session
        $this->post(route('logout'));

        // Now make 5 failed attempts — they should not be throttled because counter was reset
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
            $resp->assertStatus(302);
            $resp->assertSessionHasErrors(['email']);
        }

        // 6th attempt should be throttled
        $final = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        $final->assertStatus(429);
    }
}
