<?php

namespace Tests\Helpers;

class HubSpotFake
{
    private $contacts;
    public function __construct(array $contacts = [])
    {
        // normalize to contact shape used by HubSpotService
        $this->contacts = $contacts;
    }

    public function crm()
    {
        return new class($this->contacts) {
            private $contacts;

            public function __construct($contacts)
            {
                $this->contacts = $contacts;
            }

            public function contacts()
            {
                return new class($this->contacts) {
                    private $contacts;

                    public function __construct($contacts)
                    {
                        $this->contacts = $contacts;
                    }

                    public function getAll()
                    {
                        return $this->contacts;
                    }

                    public function update($contactId, $props)
                    {
                        // no-op for tests
                        return true;
                    }
                };
            }
        };
    }
}
