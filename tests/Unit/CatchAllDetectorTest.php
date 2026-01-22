<?php

namespace Tests\Unit;

use App\Services\CatchAllDetector;
use PHPUnit\Framework\TestCase;

class CatchAllDetectorTest extends TestCase
{
    private CatchAllDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new CatchAllDetector;
    }

    public function test_detect_returns_array_with_required_keys(): void
    {
        // Mock MX records
        $mxRecords = ['mail.example.com' => 10];

        $result = $this->detector->detect('example.com', $mxRecords);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_catch_all', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('test_results', $result);
    }

    public function test_detect_returns_boolean_for_is_catch_all(): void
    {
        $mxRecords = ['mail.example.com' => 10];

        $result = $this->detector->detect('example.com', $mxRecords);

        $this->assertIsBool($result['is_catch_all']);
    }

    public function test_detect_returns_percentage_confidence(): void
    {
        $mxRecords = ['mail.example.com' => 10];

        $result = $this->detector->detect('example.com', $mxRecords);

        $this->assertIsNumeric($result['confidence']);
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        $this->assertLessThanOrEqual(100, $result['confidence']);
    }

    public function test_detect_tracks_test_results(): void
    {
        $mxRecords = ['mail.example.com' => 10];

        $result = $this->detector->detect('example.com', $mxRecords);

        $this->assertIsInt($result['test_results']);
        $this->assertGreaterThanOrEqual(0, $result['test_results']);
        $this->assertLessThanOrEqual(3, $result['test_results']); // 3 test emails
    }
}
