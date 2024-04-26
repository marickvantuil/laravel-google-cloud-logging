<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

class Context
{
    public function __construct(private array $config)
    {
        //
    }

    public function create(): array
    {
        return array_merge_recursive(
            $this->resourceLabels(),
            $this->cloudRun(),
        );
    }

    private function resourceLabels(): array
    {
        $labels = [];

        if (! empty($this->config['location'])) {
            $labels['location'] = $this->config['location'];
        }

        return [
            'resource' => [
                'labels' => $labels,
            ],
        ];
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
                    ],
                ],
            ];
        }

        return [];
    }
}
