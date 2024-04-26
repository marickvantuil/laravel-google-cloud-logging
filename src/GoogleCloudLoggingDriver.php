<?php

declare(strict_types=1);

namespace Marick\LaravelGoogleCloudLogging;

use Google\Cloud\Logging\Logger;
use Google\Cloud\Logging\Type\LogSeverity;
use Monolog\Handler\ProcessableHandlerTrait;
use Psr\Log\LoggerInterface;
use Stringable;

class GoogleCloudLoggingDriver implements LoggerInterface
{
    use ProcessableHandlerTrait;

    public function __construct(private Logger $logger)
    {
        //
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', (string) $message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log('alert', (string) $message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log('critical', (string) $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log('error', (string) $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log('warning', (string) $message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log('notice', (string) $message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log('info', (string) $message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log('debug', (string) $message, $context);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logger->write((string) $message, [
            'severity' => match ($level) {
                'debug' => LogSeverity::DEBUG,
                'info' => LogSeverity::INFO,
                'notice' => LogSeverity::NOTICE,
                'warning' => LogSeverity::WARNING,
                'alert' => LogSeverity::ALERT,
                'critical' => LogSeverity::CRITICAL,
            },
            ...$context,
            ...(new GoogleCloudContext())->create(),
        ]);
    }
}
