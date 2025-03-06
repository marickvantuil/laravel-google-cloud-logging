<sub>Companion packages: <a href="https://github.com/stackkit/laravel-google-cloud-scheduler">Cloud Scheduler</a>, <a href="https://github.com/stackkit/laravel-google-cloud-tasks-queue">Cloud Tasks</a></sub>

# Introduction

This package lets you use Google Cloud Logging as the log driver for Laravel.

The package will automatically detect the environment it's running
in (currently supports Cloud Run or App Engine), and attach the correct labels to the log entry
so the logs appear in the application service.

# Installation

Install the package with Composer:

```console
composer require marick/laravel-google-cloud-logging
```

Add a new logging channel in `config/logging.php`:

```php
'google_cloud' => [
    'driver' => 'google_cloud',
    'location' => env('GOOGLE_CLOUD_LOGGING_LOCATION'),
],
```

Use the new channel:

```dotenv
LOG_CHANNEL=google_cloud
```

> [!IMPORTANT]
> A location is mandatory to make log entries appear in Cloud Run or App Engine.

# How to

## Use log context

```php
use Illuminate\Support\Facades\Log;

Log::debug('user logged in', [
    'user' => 5,
]);
```

The above context will be added in Cloud Logging:

```json
{
  "jsonPayload": {
    "message": "user logged in"
  },
  "labels": {
    "user": 5
  }
}
```

## Use `Context`

```php
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

Context::add('user', 5);

Log::alert('user logged in');
```

The above context will be added in Cloud Logging:

```json
{
  "jsonPayload": {
    "message": "user logged in"
  },
  "labels": {
    "user": 5
  }
}
```
