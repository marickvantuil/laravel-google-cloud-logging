<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

class Context
{
    public function create(): array
    {
        return [
            ...$this->cloudRun(),
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
