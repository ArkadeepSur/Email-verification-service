<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data()
    {
        $response = $this->post(route('register.attempt'), [
            'name' => 'Test User',
            'email' => 'test+1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test+1@example.com']);
    }

    public function test_registration_validation_errors_are_returned()
    {
        $response = $this->post(route('register.attempt'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }
}
