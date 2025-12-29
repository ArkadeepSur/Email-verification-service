<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\EmailVerificationService;

class EmailVerificationServiceTest extends TestCase
{
    public function test_validate_syntax()
    {
        $service = new EmailVerificationService($this->app->make(\App\Services\CatchAllDetector::class));

        $this->assertTrue($service->validateSyntax('user@example.com'));
        $this->assertFalse($service->validateSyntax('not-an-email'));
    }

    public function test_calculate_risk_score()
    {
        $service = new EmailVerificationService($this->app->make(\App\Services\CatchAllDetector::class));

        $base = [
            'smtp' => ['ok' => true],
            'catch_all' => ['is_catch_all' => false],
            'blacklist' => false,
            'disposable' => false,
        ];

        $this->assertSame(100, $service->calculateRiskScore($base));

        $base['smtp'] = ['ok' => false];
        $this->assertSame(50, $service->calculateRiskScore($base));

        $base['smtp'] = ['ok' => true];
        $base['catch_all'] = ['is_catch_all' => true];
        $this->assertSame(80, $service->calculateRiskScore($base));

        $base['blacklist'] = true;
        $this->assertSame(0, $service->calculateRiskScore($base));
    }
}
