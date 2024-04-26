<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Google\Cloud\Logging\Logger;
use Google\Cloud\Logging\Type\LogSeverity;
use Monolog\Handler\Handler as MonologHandler;
use Monolog\LogRecord;

class Handler extends MonologHandler
{
    public function __construct(private Logger $logger, private array $config = [])
    {
        //
    }

    public function isHandling(LogRecord $record): bool
    {
        return true;
    }

    public function handle(LogRecord $record): bool
    {
        $this->logger->write($record->message, [
            'severity' => match ($record->level->name) {
                'Debug' => LogSeverity::DEBUG,
                'Info' => LogSeverity::INFO,
                'Notice' => LogSeverity::NOTICE,
                'Warning' => LogSeverity::WARNING,
                'Alert' => LogSeverity::ALERT,
                'Critical' => LogSeverity::CRITICAL,
            },
            $record->context,
            ...(new Context($this->config))->create(),
        ]);

        return true;
    }
}
