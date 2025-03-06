<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;
use Google\Cloud\Logging\LoggingClient;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        Log::extend('google_cloud', function (Application $app, array $config) {
            return new Logger(
                name: 'google_cloud',
                handlers: [
                    new Handler($config),
                ],
                processors: [],
            );
        });

        app()->bind('cloud_logging', function () {
            $client = new LoggingClient;

            return $client->logger('laravel');
        });
    }
}
