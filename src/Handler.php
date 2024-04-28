<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Google\Cloud\Logging\Type\LogSeverity;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\Handler as MonologHandler;
use Monolog\LogRecord;
use Throwable;

class Handler extends MonologHandler
{
    public function __construct(private array $config = [])
    {
        //
    }

    public function isHandling(LogRecord $record): bool
    {
        return true;
    }

    public function handle(LogRecord $record): bool
    {
        try {
            $enrichLog = new EnrichLog($this->config, $record->context);

            CloudLogging::write(
                [
                    'message' => $record->message,
                    ...$enrichLog->enrichedEntry(),
                ],
                [
                    'severity' => match ($record->level->name) {
                        'Debug' => LogSeverity::DEBUG,
                        'Info' => LogSeverity::INFO,
                        'Notice' => LogSeverity::NOTICE,
                        'Warning' => LogSeverity::WARNING,
                        'Error' => LogSeverity::ERROR,
                        'Alert' => LogSeverity::ALERT,
                        'Critical' => LogSeverity::CRITICAL,
                        'Emergency' => LogSeverity::EMERGENCY,
                    },
                    ...$enrichLog->enrichedOptions(),
                ]);
        } catch (Throwable $throwable) {
            Log::driver('stack')->error((string) $throwable);

            Log::driver('stack')->{strtolower($record->level->name)}(
                $record->message,
                $record->context
            );
        }

        return true;
    }
}
