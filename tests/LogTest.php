<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Context;
use Marick\LaravelGoogleCloudLogging\CloudLogging;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class LogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CloudLogging::fake();

        Env::getRepository()->clear('K_SERVICE');
        Env::getRepository()->clear('K_REVISION');
        Env::getRepository()->clear('GAE_SERVICE');
        Env::getRepository()->clear('GAE_VERSION');
    }

    #[Test]
    public function it_can_log(): void
    {
        // Act
        logger()->emergency('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $entry['message'] === 'hey'
                && $options['severity'] === 800;
        });
    }

    #[Test]
    #[TestWith(['debug', 100])]
    #[TestWith(['info', 200])]
    #[TestWith(['notice', 300])]
    #[TestWith(['warning', 400])]
    #[TestWith(['error', 500])]
    #[TestWith(['critical', 600])]
    #[TestWith(['alert', 700])]
    #[TestWith(['emergency', 800])]
    public function it_can_log_all_levels(string $level, int $expectedSeverity): void
    {
        // Act
        logger()->$level('user logged in');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options) use ($expectedSeverity): bool {
            return $entry['message'] === 'user logged in'
                && $options['severity'] === $expectedSeverity;
        });
    }

    #[Test]
    public function context_can_be_added(): void
    {
        // Arrange
        if (! class_exists(Context::class)) {
            $this->markTestSkipped('Context not supported.');
        }

        // Act
        Context::add('user', 5);
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry): bool {
            return $entry['illuminate:log:context'] === ['user' => 5];
        });
    }

    #[Test]
    public function http_request_will_be_added(): void
    {
        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $options['httpRequest']['requestMethod'] === 'GET'
                && $options['httpRequest']['requestUrl'] === 'http://localhost';
        });
    }

    #[Test]
    public function cloud_run_context_will_be_added(): void
    {
        // Arrange
        Env::getRepository()->set('K_SERVICE', 'my-service');
        Env::getRepository()->set('K_REVISION', 'abc123');

        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $options['resource'] === [
                'type' => 'cloud_run_revision',
                'labels' => [
                    'service_name' => 'my-service',
                    'revision_name' => 'abc123',
                ],
            ];
        });
    }

    #[Test]
    public function location_can_be_added_to_cloud_run_context(): void
    {
        // Arrange
        Env::getRepository()->set('K_SERVICE', 'my-service');
        Env::getRepository()->set('K_REVISION', 'abc123');
        app('config')->set('logging.channels.google_cloud.location', 'us-central1');

        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $options['resource'] === [
                'type' => 'cloud_run_revision',
                'labels' => [
                    'service_name' => 'my-service',
                    'revision_name' => 'abc123',
                    'location' => 'us-central1',
                ],
            ];
        });
    }

    #[Test]
    public function app_engine_context_will_be_added(): void
    {
        // Arrange
        Env::getRepository()->set('GAE_SERVICE', 'my-service');
        Env::getRepository()->set('GAE_VERSION', 'abc123');

        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $options['resource'] === [
                'type' => 'gae_app',
                'labels' => [
                    'module_id' => 'my-service',
                    'version_id' => 'abc123',
                ],
            ];
        });
    }

    #[Test]
    public function labels_can_be_added_through_context(): void
    {
        // Act
        logger()->debug('hey', [
            'user_agent' => 'example user agent',
            'user' => 512,
        ]);

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $options['labels'] === [
                'user_agent' => 'example user agent',
                'user' => 512,
            ];
        });
    }
}
