<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Closure;
use Google\Cloud\Logging\Logger;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin Logger
 */
class CloudLogging extends Facade
{
    private static array $labels = [];

    protected static function getFacadeAccessor()
    {
        return 'cloud_logging';
    }

    public static function withLabels(array|Closure $labels): void
    {
        static::$labels[] = $labels;
    }

    public static function getLabels(): array
    {
        return array_merge(...array_map(
            fn ($labels) => value($labels),
            static::$labels,
        ));
    }

    public static function fake(): void
    {
        static::$labels = [];
        self::swap(new FakeCloudLogger);
    }
}
