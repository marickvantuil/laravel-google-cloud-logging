<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Closure;
use Tests\TestCase;

class FakeCloudLogger
{
    public function __construct(private array $logged = [])
    {
        //
    }

    public function write(array $entry, array $options): void
    {
        $this->logged[] = compact('entry', 'options');
    }

    public function assertWritten(Closure $closure): void
    {
        $matches = [];

        foreach ($this->logged as $entry) {
            if ($closure($entry['entry'], $entry['options'])) {
                $matches[] = $entry;
            }
        }

        TestCase::assertNotEmpty($matches, 'The given information was not logged.');
    }
}
