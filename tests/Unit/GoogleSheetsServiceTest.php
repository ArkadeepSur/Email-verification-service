<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use App\Services\GoogleSheetsService;
use App\Jobs\VerifyBulkEmailsJob;
use Google\Service\Sheets;

class GoogleSheetsServiceTest extends TestCase
{
    public function test_import_emails_dispatches_job()
    {
        Queue::fake();

        // Mock Sheets service
        $sheets = Mockery::mock(Sheets::class);

        // Mock spreadsheets_values sub-resource
        $sheets->spreadsheets_values = Mockery::mock();
        $sheets->spreadsheets_values
            ->shouldReceive('get')
            ->once()
            ->with('spreadsheet-id', 'Sheet1!A:A')
            ->andReturn((object) [
                'values' => [
                    ['email'],
                    ['test@example.com'],
                ],
            ]);

        $service = new GoogleSheetsService($sheets);

        $service->importEmails(
            'spreadsheet-id',
            'Sheet1!A:A',
            1
        );

        Queue::assertPushed(VerifyBulkEmailsJob::class, function ($job) {
            return $job->emails === ['test@example.com']
                && $job->userId === 1;
        });
    }
}
