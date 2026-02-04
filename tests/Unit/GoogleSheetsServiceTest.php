use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use App\Services\GoogleSheetsService;
use App\Jobs\VerifyEmailJob;
use Mockery;

class GoogleSheetsServiceTest extends TestCase
{
    public function test_import_emails_dispatches_job()
    {
        Queue::fake();

        // Mock the Sheets service (NOT the real constructor)
        $sheets = Mockery::mock(\Google\Service\Sheets::class);

        // Mock the API call chain
        $sheets->spreadsheets_values = Mockery::mock();
        $sheets->spreadsheets_values
            ->shouldReceive('get')
            ->once()
            ->andReturn((object) [
                'values' => [
                    ['email'],
                    ['test@example.com'],
                ],
            ]);

        $service = new GoogleSheetsService($sheets);

        $service->importEmails('spreadsheet-id');

        Queue::assertPushed(VerifyEmailJob::class);
    }
}
