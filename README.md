# Introduction

This package lets you use Google Cloud Logging as the log driver for Laravel.

The package will automatically detect the environment it's running
in (Cloud Run or App Engine), and attach the correct labels to the log entry
so in Google Cloud you can view the logs for each service.

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


