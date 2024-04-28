<?php

declare(strict_types=1);

namespace Tests;

use Google\Cloud\Logging\LoggingClient;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class CloudLoggerTest extends TestCase
{
    private LoggingClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new LoggingClient();
    }

    #[Test]
    public function it_sends_logs_to_google(): void
    {
        // Arrange
        $label = Str::random();
        $timestamp = now()->toIso8601String();
        logger('hello', [
            'my_label' => $label,
        ]);

        // Act
        $entries = $this->client->entries([
            'filter' => implode("\n", [
                'timestamp >= "'.$timestamp.'"',
                'labels.my_label="'.$label.'"',
            ]),
        ]);

        $matches = [];
        $start = time();

        while (true) {
            if (time() - $start >= 30) {
                break;
            }

            foreach ($entries as $entry) {
                $matches[] = $entry;

                break 2;
            }

            sleep(1);
        }

        $this->assertCount(1, $matches);
    }
}
