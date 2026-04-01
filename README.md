<sub>Companion packages: <a href="https://github.com/stackkit/laravel-google-cloud-scheduler">Cloud Scheduler</a>, <a href="https://github.com/stackkit/laravel-google-cloud-tasks-queue">Cloud Tasks</a></sub>

# Introduction

This package lets you use Google Cloud Logging as the log driver for Laravel.

The package will automatically detect the environment it's running
in (supports Cloud Run, App Engine, and GKE), and attach the correct labels to the log entry
so the logs appear in the application service.

This package requires Laravel 12 or 13.

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

Context is added to the JSON payload alongside the message:

```json
{
  "jsonPayload": {
    "message": "user logged in",
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

Laravel `Context` values are added as labels (useful for filtering in Cloud Logging):

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

## Configure the log name

By default, log entries are written to the `laravel` log. You can change this per channel:

```php
'google_cloud' => [
    'driver' => 'google_cloud',
    'location' => env('GOOGLE_CLOUD_LOGGING_LOCATION'),
    'log_name' => env('GOOGLE_CLOUD_LOG_NAME', 'laravel'),
],
```

## Trace correlation

When running on Cloud Run or behind a GCP load balancer, the `X-Cloud-Trace-Context` header is automatically injected into requests. The package reads this header and attaches trace information to log entries, linking them to Cloud Trace:

```json
{
  "trace": "projects/my-project/traces/105445aa7843bc8bf206b12000100000",
  "spanId": "1",
  "traceSampled": true
}
```

This requires the `GOOGLE_CLOUD_PROJECT` (or `GCLOUD_PROJECT`) environment variable to be set.

## GKE resource detection

When running on Google Kubernetes Engine, the package detects the environment via the `KUBERNETES_SERVICE_HOST` env var (automatically injected into all pods) and sets the `k8s_container` resource type. Set the following env vars (typically via the [Downward API](https://kubernetes.io/docs/concepts/workloads/pods/downward-api/)) to populate the resource labels:

| Env var | GCP label |
|---|---|
| `KUBERNETES_CLUSTER_NAME` | `cluster_name` |
| `KUBERNETES_NAMESPACE` | `namespace_name` |
| `KUBERNETES_POD_NAME` | `pod_name` |
| `KUBERNETES_CONTAINER_NAME` | `container_name` |

## Source location

Each log entry automatically includes the file, line number, and function where the log was called:

```json
{
  "sourceLocation": {
    "file": "/var/www/html/app/Http/Controllers/UserController.php",
    "line": "42",
    "function": "App\\Http\\Controllers\\UserController::store"
  }
}
```

## HTTP request metadata

Each log entry includes HTTP request details:

```json
{
  "httpRequest": {
    "requestMethod": "GET",
    "requestUrl": "https://example.com/users",
    "userAgent": "Mozilla/5.0 ...",
    "remoteIp": "1.2.3.4",
    "referer": "https://example.com",
    "protocol": "HTTPS"
  }
}
```
