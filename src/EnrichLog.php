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
            $this->logContext(),
            $this->laravelContext(),
        );

    }

    private function httpRequest(): array
    {
        return [
            'httpRequest' => [
                'requestMethod' => request()->method(),
                'requestUrl' => request()->url(),
            ],
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
