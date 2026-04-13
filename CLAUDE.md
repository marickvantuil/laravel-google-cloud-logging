# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

Run all commands inside the Docker container:

```bash
# Run tests (fast, uses CloudLogging::fake())
docker compose run --rm app composer test

# Run a single test
docker compose run --rm app vendor/bin/testbench package:test --filter=it_can_log

# Run the integration test (hits real GCP, requires GOOGLE_APPLICATION_CREDENTIALS)
docker compose run --rm app vendor/bin/testbench package:test --filter=CloudLoggerTest

# Lint (pint + phpstan)
docker compose run --rm app composer lint

# Switch to a specific Laravel version for local dev
docker compose run --rm app composer l12
docker compose run --rm app composer l13
```

## Architecture

The package registers a custom `google_cloud` Monolog driver via `ServiceProvider`. The flow is:

1. `ServiceProvider` — registers the `google_cloud` log driver and binds `cloud_logging` (a `Google\Cloud\Logging\Logger`) in the container. The `CloudLogging` facade wraps this binding.
2. `Handler` — a Monolog handler that receives each log record, maps Laravel/Monolog log levels to GCP `LogSeverity` constants, and calls `CloudLogging::write()`.
3. `EnrichLog` — builds the `$options` array passed to `write()`. Detects the runtime environment (Cloud Run via `K_SERVICE`/`K_REVISION` env vars, App Engine via `GAE_SERVICE`/`GAE_VERSION`) and attaches the appropriate `resource` labels so logs surface correctly in GCP. Also attaches `httpRequest` data and any Laravel `Context` values as labels.
4. `FakeCloudLogger` — swapped in via `CloudLogging::fake()` in tests. Captures `write()` calls and exposes `assertWritten(Closure)` for assertions.

## Testing approach

- `LogTest` covers all behaviour using `CloudLogging::fake()` — no real GCP calls.
- `CloudLoggerTest` is an integration test that sends a real log entry and polls GCP for up to 30 seconds. It requires `GOOGLE_APPLICATION_CREDENTIALS` pointing to a valid service account key (`tests/key.json` in CI).
- Tests use Orchestra Testbench with `WithWorkbench`. `TestCase::getEnvironmentSetUp` sets the default log channel to `google_cloud`.
