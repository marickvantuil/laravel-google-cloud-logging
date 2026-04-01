<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Illuminate\Support\Facades\Context;

class EnrichLog
{
    public function __construct(private array $config, private array $context)
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
        if (! empty($this->context)) {
            return [
                'labels' => $this->context,
            ];
        }

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
