<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Marick\LaravelGoogleCloudLogging\ServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('logging.channels.google_cloud', [
            'driver' => 'google_cloud',
        ]);

        $app['config']->set('logging.default', 'google_cloud');
    }
}
