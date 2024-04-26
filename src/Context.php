<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

class Context
{
    public function __construct(private array $config, private array $context)
    {
        //
    }

    public function create(): array
    {
        return array_merge_recursive(
            $this->httpRequest(),
            $this->cloudRun(),
            $this->appEngine(),
            $this->labels(),
        );
    }

    private function httpRequest(): array
    {
        if (app()->runningInConsole()) {
            return [];
        }

        return [
            'httpRequest' => [
                'requestMethod' => request()->method(),
                'requestUrl' => request()->url(),
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

    private function cloudRun(): array
    {
        $revision = env('K_REVISION');
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
        $service = env('GAE_SERVICE');
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

    private function labels(): array
    {
        if (! empty($this->context)) {
            return [
                'labels' => $this->context,
            ];
        }

        return [];
    }
}
