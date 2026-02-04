<?php

namespace Tests\Unit;

use App\Jobs\VerifyBulkEmailsJob;
use App\Services\GoogleSheetsService;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class GoogleSheetsServiceTest extends TestCase
{
    public function test_import_emails_dispatches_job()
    {
        Queue::fake();

        $sheets = Mockery::mock(Sheets::class);

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
            return $job->userId === 1
                && $job->emails === ['test@example.com'];
        });
    }
}
