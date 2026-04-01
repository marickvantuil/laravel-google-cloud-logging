<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Env;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\TestWith;
use Marick\LaravelGoogleCloudLogging\CloudLogging;

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
        Env::getRepository()->clear('KUBERNETES_SERVICE_HOST');
        Env::getRepository()->clear('KUBERNETES_CLUSTER_NAME');
        Env::getRepository()->clear('KUBERNETES_NAMESPACE');
        Env::getRepository()->clear('KUBERNETES_POD_NAME');
        Env::getRepository()->clear('KUBERNETES_CONTAINER_NAME');
        Env::getRepository()->clear('GOOGLE_CLOUD_PROJECT');
        Env::getRepository()->clear('GCLOUD_PROJECT');
    }

    #[Test]
    public function log_name_can_be_configured(): void
    {
        // Arrange
        app('config')->set('logging.channels.google_cloud.log_name', 'my-app');

        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $entry['message'] === 'hey';
        });
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
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $options['labels'] === [
                'user' => 5,
            ];
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
            return ($entry['user_agent'] ?? null) === 'example user agent'
                && ($entry['user'] ?? null) === 512;
        });
    }

    #[Test]
    public function nested_context_values_are_in_entry_payload(): void
    {
        // Act
        logger()->debug('hey', [
            'user' => ['id' => 1, 'name' => 'Alice'],
            'tags' => ['admin', 'editor'],
        ]);

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return ($entry['user'] ?? null) === ['id' => 1, 'name' => 'Alice']
                && ($entry['tags'] ?? null) === ['admin', 'editor'];
        });
    }

    #[Test]
    public function http_request_includes_user_agent_and_remote_ip(): void
    {
        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return isset($options['httpRequest']['userAgent'])
                && isset($options['httpRequest']['remoteIp']);
        });
    }

    #[Test]
    public function http_request_includes_referer_when_present(): void
    {
        // Arrange
        request()->headers->set('referer', 'https://example.com');

        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return ($options['httpRequest']['referer'] ?? null) === 'https://example.com';
        });
    }

    #[Test]
    public function http_request_includes_protocol(): void
    {
        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return isset($options['httpRequest']['protocol']);
        });
    }

    #[Test]
    public function source_location_is_attached(): void
    {
        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return isset($options['sourceLocation']['file'])
                && ! empty($options['sourceLocation']['file']);
        });
    }

    #[Test]
    public function trace_correlation_is_attached(): void
    {
        // Arrange
        Env::getRepository()->set('GOOGLE_CLOUD_PROJECT', 'my-project');
        request()->headers->set('X-Cloud-Trace-Context', '105445aa7843bc8bf206b12000100000/1;o=1');

        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return ($options['trace'] ?? null) === 'projects/my-project/traces/105445aa7843bc8bf206b12000100000'
                && ($options['spanId'] ?? null) === '1'
                && ($options['traceSampled'] ?? null) === true;
        });
    }

    #[Test]
    public function trace_is_not_attached_without_header(): void
    {
        // Arrange
        Env::getRepository()->set('GOOGLE_CLOUD_PROJECT', 'my-project');

        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return ! isset($options['trace']);
        });
    }

    #[Test]
    public function gke_resource_will_be_added(): void
    {
        // Arrange
        Env::getRepository()->set('KUBERNETES_SERVICE_HOST', '10.0.0.1');
        Env::getRepository()->set('KUBERNETES_CLUSTER_NAME', 'my-cluster');
        Env::getRepository()->set('KUBERNETES_NAMESPACE', 'default');
        Env::getRepository()->set('KUBERNETES_POD_NAME', 'my-pod-abc');

        // Act
        logger()->debug('hey');

        // Assert
        CloudLogging::assertWritten(function (array $entry, array $options): bool {
            return $options['resource'] === [
                'type' => 'k8s_container',
                'labels' => [
                    'cluster_name' => 'my-cluster',
                    'namespace_name' => 'default',
                    'pod_name' => 'my-pod-abc',
                ],
            ];
        });
    }
}
