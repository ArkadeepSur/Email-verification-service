<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_email_service_syntax_validation()
    {
        $service = $this->app->make(\App\Services\EmailVerificationService::class);
        $this->assertTrue($service->validateSyntax('user@example.com'));
        $this->assertFalse($service->validateSyntax('not-an-email'));
    }
}
