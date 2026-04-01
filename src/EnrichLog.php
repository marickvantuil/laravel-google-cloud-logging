<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Illuminate\Support\Facades\Context;

class EnrichLog
{
    public function __construct(private array $config, private array $context, private array $extra = [])
    {
        //
    }

    public function options(): array
    {
        return array_merge_recursive(
            $this->httpRequest(),
            $this->cloudRun(),
            $this->appEngine(),
            $this->gke(),
            $this->logContext(),
            $this->laravelContext(),
            $this->sourceLocation(),
            $this->trace(),
        );

    }

    private function httpRequest(): array
    {
        $fields = array_filter([
            'requestMethod' => request()->method(),
            'requestUrl' => request()->url(),
            'userAgent' => request()->userAgent(),
            'remoteIp' => request()->ip(),
            'referer' => request()->header('referer'),
            'protocol' => request()->secure() ? 'HTTPS' : 'HTTP',
        ], fn ($value) => $value !== null && $value !== '');

        return [
            'httpRequest' => $fields,
        ];
    }

    private function cloudRun(): array
    {
        // @phpstan-ignore-next-line
        $revision = env('K_REVISION');
        // @phpstan-ignore-next-line
        $service = env('K_SERVICE');

        if ($revision && $service) {
            return [
                'resource' => [
                    'type' => 'cloud_run_revision',
                    'labels' => [
                        'service_name' => $service,
                        'revision_name' => $revision,
                        ...$this->location(),
                    ],
                ],
            ];
        }

        return [];
    }

    private function appEngine(): array
    {
        // @phpstan-ignore-next-line
        $service = env('GAE_SERVICE');
        // @phpstan-ignore-next-line
        $version = env('GAE_VERSION');

        if ($service && $version) {
            return [
                'resource' => [
                    'type' => 'gae_app',
                    'labels' => [
                        'module_id' => $service,
                        'version_id' => $version,
                    ],
                ],
            ];
        }

        return [];
    }

    private function trace(): array
    {
        $header = request()->header('X-Cloud-Trace-Context');

        if (! $header) {
            return [];
        }

        // @phpstan-ignore-next-line
        $project = env('GOOGLE_CLOUD_PROJECT') ?? env('GCLOUD_PROJECT');

        if (! $project) {
            return [];
        }

        // Format: TRACE_ID/SPAN_ID;o=SAMPLE_DECISION
        if (! preg_match('/^([a-f0-9]+)(?:\/([^;]+))?(?:;o=(\d+))?$/', $header, $matches)) {
            return [];
        }

        $traceId = $matches[1] ?? null;
        $spanId = $matches[2] ?? null;
        $sampled = isset($matches[3]) ? $matches[3] === '1' : null;

        return array_filter([
            'trace' => $traceId ? "projects/{$project}/traces/{$traceId}" : null,
            'spanId' => $spanId ?: null,
            'traceSampled' => $sampled,
        ], fn ($value) => $value !== null);
    }

    private function sourceLocation(): array
    {
        $location = array_filter([
            'file' => $this->extra['file'] ?? null,
            'line' => isset($this->extra['line']) ? (string) $this->extra['line'] : null,
            'function' => $this->extra['function'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if (empty($location)) {
            return [];
        }

        return [
            'sourceLocation' => $location,
        ];
    }

    private function gke(): array
    {
        // @phpstan-ignore-next-line
        if (! env('KUBERNETES_SERVICE_HOST')) {
            return [];
        }

        $labels = array_filter([
            // @phpstan-ignore-next-line
            'cluster_name' => env('KUBERNETES_CLUSTER_NAME'),
            // @phpstan-ignore-next-line
            'namespace_name' => env('KUBERNETES_NAMESPACE'),
            // @phpstan-ignore-next-line
            'pod_name' => env('KUBERNETES_POD_NAME'),
            // @phpstan-ignore-next-line
            'container_name' => env('KUBERNETES_CONTAINER_NAME'),
            ...$this->location(),
        ], fn ($value) => $value !== null && $value !== '');

        return [
            'resource' => [
                'type' => 'k8s_container',
                'labels' => $labels,
            ],
        ];
    }

    private function location(): array
    {
        if (! empty($this->config['location'])) {
            return [
                'location' => $this->config['location'],
            ];
        }

        return [];
    }

    private function logContext(): array
    {
        return [];
    }

    private function laravelContext(): array
    {
        if (! class_exists(Context::class)) {
            return [];
        }

        if (Context::isEmpty()) {
            return [];
        }

        return [
            'labels' => Context::all(),
        ];
    }
}
