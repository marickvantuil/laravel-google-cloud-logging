<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Google\Cloud\Logging\LoggingClient;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        Log::extend('google_cloud', function (Application $app, array $config) {
            $client = new LoggingClient();

            $logger = $client->logger('laravel');

            return new Logger(
                name: 'google_cloud',
                handlers: [
                    new Handler($logger, $config),
                ],
                processors: [],
            );
        });
    }
}
