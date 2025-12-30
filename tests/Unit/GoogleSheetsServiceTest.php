<?php

namespace Tests\Unit;

use App\Jobs\VerifyBulkEmailsJob;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class GoogleSheetsServiceTest extends TestCase
{
    public function test_import_emails_dispatches_job()
    {
        Bus::fake();

        $serviceMock = Mockery::mock(\App\Services\GoogleSheetsService::class)->makePartial();

        // Create a fake spreadsheets_values object that responds to get()
        $fakeSpreadsheetValues = new class
        {
            public function get()
            {
                $fakeResponse = new class
                {
                    public function getValues()
                    {
                        return [['user@example.com'], ['not-an-email'], ['another@example.com']];
                    }
                };

                return $fakeResponse;
            }
        };

        // Create a fake client with spreadsheets_values property
        $fakeClient = new class($fakeSpreadsheetValues)
        {
            public $spreadsheets_values;

            public function __construct($sv)
            {
                $this->spreadsheets_values = $sv;
            }
        };

        $serviceMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getGoogleClient')
            ->andReturn($fakeClient);

        $this->app->instance(\App\Services\GoogleSheetsService::class, $serviceMock);

        app(\App\Services\GoogleSheetsService::class)->importEmails('sheet', 'range');

        Bus::assertDispatched(VerifyBulkEmailsJob::class, function ($job) {
            return is_array($job->emails) && in_array('user@example.com', $job->emails) && in_array('another@example.com', $job->emails);
        });

        Mockery::close();
    }
}
