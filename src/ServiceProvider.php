<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;
use Google\Cloud\Logging\LoggingClient;
use Monolog\Processor\IntrospectionProcessor;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        Log::extend('google_cloud', function (Application $app, array $config) {
            $app->bind('cloud_logging', function () use ($config) {
                $client = new LoggingClient;

                return $client->logger($config['log_name'] ?? 'laravel');
            });

            return new Logger(
                name: 'google_cloud',
                handlers: [
                    new Handler($config),
                ],
                processors: [
                    new IntrospectionProcessor(
                        skipClassesPartials: ['Illuminate\\', 'Marick\\', 'Psr\\'],
                    ),
                ],
            );
        });
    }
}
