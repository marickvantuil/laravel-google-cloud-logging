<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Google\Cloud\Logging\LoggingClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CloudLoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Log::extend('google_cloud', function () {
            $client = new LoggingClient();

            $logger = $client->logger('laravel');

            return new GoogleCloudLoggingDriver($logger);
        });
    }
}
