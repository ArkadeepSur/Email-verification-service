<?php

namespace Tests\Helpers;

class GoogleSheetsFake
{
    public $spreadsheets_values;

    public function __construct(array $values = [])
    {
        $this->spreadsheets_values = new class($values)
        {
            private $values;

            public function __construct($values)
            {
                $this->values = $values;
            }

            public function get($spreadsheetId, $range)
            {
                return new class($this->values)
                {
                    private $values;

                    public function __construct($values)
                    {
                        $this->values = $values;
                    }

                    public function getValues()
                    {
                        return $this->values;
                    }
                };
            }

            public function update($spreadsheetId, $range, $body, $opts = [])
            {
                // No-op for tests. Could record calls if needed.
                return true;
            }
        };
    }
}
