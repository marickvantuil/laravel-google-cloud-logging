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

Context is nested under `logContext` in the JSON payload, supports any structure:

```json
{
  "jsonPayload": {
    "message": "user logged in",
    "logContext": {
      "user": 5
    }
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

Laravel `Context` values are nested under `laravelContext` in the JSON payload:

```json
{
  "jsonPayload": {
    "message": "user logged in",
    "laravelContext": {
      "user": 5
    }
  }
}
```

## Add labels

GCP labels are flat string key-value pairs, useful for filtering across log entries. There are three ways to add them:

**Via config** — applied to every log entry in the channel:

```php
'google_cloud' => [
    'driver' => 'google_cloud',
    'labels' => ['environment' => 'production'],
],
```

**Per log entry** — pass a `labels` key in the log context:

```php
Log::debug('user logged in', [
    'labels' => ['request_id' => $requestId],
]);
```

**Per request** — set once (e.g. in middleware), applied to all log entries for the request:

```php
use Marick\LaravelGoogleCloudLogging\CloudLogging;

CloudLogging::withLabels(['tenant_id' => $tenantId]);

// Or lazily via a closure, resolved at log time:
CloudLogging::withLabels(fn () => ['user_id' => auth()->id()]);
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

## Automatic enrichment

Every log entry is automatically enriched with the following:

### HTTP request

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

### Source location

```json
{
  "sourceLocation": {
    "file": "/var/www/html/app/Http/Controllers/UserController.php",
    "line": "42",
    "function": "App\\Http\\Controllers\\UserController::store"
  }
}
```

### Trace correlation

When the `X-Cloud-Trace-Context` header is present (injected automatically on Cloud Run and behind GCP load balancers), trace information is attached to link log entries to Cloud Trace. Requires `GOOGLE_CLOUD_PROJECT` (or `GCLOUD_PROJECT`) to be set.

```json
{
  "trace": "projects/my-project/traces/105445aa7843bc8bf206b12000100000",
  "spanId": "1",
  "traceSampled": true
}
```

### Resource detection

The package detects the runtime environment and sets the correct GCP resource type so logs surface in the right place.

**Cloud Run** — detected via `K_SERVICE` and `K_REVISION`:

```json
{
  "resource": {
    "type": "cloud_run_revision",
    "labels": { "service_name": "my-service", "revision_name": "my-service-00001-abc", "location": "europe-west1" }
  }
}
```

**App Engine** — detected via `GAE_SERVICE` and `GAE_VERSION`:

```json
{
  "resource": {
    "type": "gae_app",
    "labels": { "module_id": "my-service", "version_id": "v1" }
  }
}
```

**GKE** — detected via `KUBERNETES_SERVICE_HOST` (automatically present in all pods). Set the following env vars via the [Downward API](https://kubernetes.io/docs/concepts/workloads/pods/downward-api/) to populate the resource labels:

| Env var | Resource label |
|---|---|
| `KUBERNETES_CLUSTER_NAME` | `cluster_name` |
| `KUBERNETES_NAMESPACE` | `namespace_name` |
| `KUBERNETES_POD_NAME` | `pod_name` |
| `KUBERNETES_CONTAINER_NAME` | `container_name` |
