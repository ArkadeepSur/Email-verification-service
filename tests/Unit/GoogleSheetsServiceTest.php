<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use App\Jobs\VerifyBulkEmailsJob;
use Mockery;

class GoogleSheetsServiceTest extends TestCase
{
    public function test_import_emails_dispatches_job()
    {
        Bus::fake();

        $serviceMock = Mockery::mock(\App\Services\GoogleSheetsService::class)->makePartial();

        $fakeValues = (object) ['getValues' => function () {
            return [['user@example.com'], ['not-an-email'], ['another@example.com']];
        }];

        $fakeSpreadsheets = (object) ['get' => function () use ($fakeValues) {
            return $fakeValues;
        }];

        $fakeService = (object) ['spreadsheets_values' => $fakeSpreadsheets];

        $serviceMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getGoogleClient')
            ->andReturn(new class($fakeService) {
                public $service;
                public function __construct($service) { $this->service = $service; }
                public function __get($name) { return $this->service->{$name}; }
            });

        $this->app->instance(\App\Services\GoogleSheetsService::class, $serviceMock);

        app(\App\Services\GoogleSheetsService::class)->importEmails('sheet', 'range');

        Bus::assertDispatched(VerifyBulkEmailsJob::class, function ($job) {
            // job dispatch receives array with 2 valid emails
            return is_array($job->emails) && in_array('user@example.com', $job->emails) && in_array('another@example.com', $job->emails);
        });

        Mockery::close();
    }
}
