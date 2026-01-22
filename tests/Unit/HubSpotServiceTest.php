<?php

namespace Tests\Unit;

use App\Jobs\VerifyBulkEmailsJob;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class HubSpotServiceTest extends TestCase
{
    public function test_sync_contacts_dispatches_job()
    {
        Bus::fake();

        $serviceMock = Mockery::mock(\App\Services\HubSpotService::class)->makePartial();

        $fakeContact1 = (object) ['properties' => (object) ['email' => 'user1@example.com']];
        $fakeContact2 = (object) ['properties' => (object) ['email' => 'user2@example.com']];

        $fakeClient = new class($fakeContact1, $fakeContact2)
        {
            private $c1;

            private $c2;

            public function __construct($c1, $c2)
            {
                $this->c1 = $c1;
                $this->c2 = $c2;
            }

            public function crm()
            {
                return $this;
            }

            public function contacts()
            {
                return $this;
            }

            public function getAll()
            {
                return [$this->c1, $this->c2];
            }
        };

        $serviceMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getHubSpotClient')
            ->andReturn($fakeClient);

        $this->app->instance(\App\Services\HubSpotService::class, $serviceMock);

        app(\App\Services\HubSpotService::class)->syncContacts([]);

        Bus::assertDispatched(VerifyBulkEmailsJob::class, function ($job) {
            return is_array($job->emails) && in_array('user1@example.com', $job->emails);
        });

        Mockery::close();
    }
}
