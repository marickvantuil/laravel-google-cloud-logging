<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Illuminate\Support\Facades\Facade;

class CloudLogging extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cloud_logging';
    }

    public static function fake(): void
    {
        self::swap(new FakeCloudLogger());
    }
}
