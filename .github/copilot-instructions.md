# Copilot / AI agent instructions for Email Verification Service 🚀

Purpose
-------
- Short: help an AI contributor become productive quickly by describing the architecture, key code locations, development workflows, conventions, and integration points.

Big picture
-----------
- Laravel-based service that verifies emails using a mix of synchronous checks and background jobs.
- Core flow: VerifyBulkEmailsJob -> VerifyEmailJob -> EmailVerificationService -> CatchAllDetector -> persist VerificationResult (see: `app/Jobs/VerifyBulkEmailsJob.php`, `app/Jobs/VerifyEmailJob.php`, `app/Services/EmailVerificationService.php`, `app/Services/CatchAllDetector.php`, `app/Models/VerificationResult.php`).
- Integrations: Google Sheets import/export (`app/Services/GoogleSheetsService.php`), HubSpot sync (`app/Services/HubSpotService.php`), and webhooks (`app/Services/WebhookService.php`, `app/Jobs/SendWebhookJob.php`).
- Observability: metrics via `App\Services\Metrics\MetricsPublisher` with implementations in `app/Services/Metrics/*` (default driver controlled by `config/metrics.php`).

Where to start reading (priority)
---------------------------------
1. `app/Jobs/VerifyEmailJob.php` — orchestrates verification steps.
2. `app/Services/EmailVerificationService.php` — core verification rules (syntax, MX, SMTP, blacklist, scoring).
3. `app/Services/CatchAllDetector.php` — domain-level catch-all detection logic and SMTP checks.
4. `app/Services/WebhookService.php` + `app/Jobs/SendWebhookJob.php` — how and when webhooks are emitted.
5. `app/Providers/VerificationServiceProvider.php` — DI registration (how services are bound).
6. `database/migrations/` + `database/seeders/DevSeeder.php` — schema and dev seed data (admin@example.com).

Developer workflows (commands you should use)
--------------------------------------------
- Project setup: follow `SCaffolding_README.md`.
  - Typical: `composer install`, copy `.env.example` → `.env` (note: `.env.example` may be missing; create with required keys), `php artisan key:generate`, `php artisan migrate`.
- Local dev: `composer dev` (runs `php artisan serve`, `php artisan queue:listen`, `pail` logs watcher, and `npm run dev` via `concurrently`), or run the pieces manually:
  - `php artisan serve`, `php artisan queue:work --tries=3` (or `php artisan horizon` for Redis/Horizon setups).
- Tests: `composer test` (runs `php artisan test`). Unit/integration tests are configured to use SQLite in-memory and `QUEUE_CONNECTION=sync` as in `phpunit.xml` — write tests accordingly.
- Useful composer scripts: `composer setup` (installs deps, migrates, builds assets).

Project-specific conventions & patterns
--------------------------------------
- Business logic lives in `app/Services/*` (stateless service classes). Jobs orchestrate work and call services (e.g., `VerifyEmailJob` delegates to `EmailVerificationService`).
- Use Jobs for any background/async work and to keep controllers thin.
- Events + Listeners decouple side-effects (e.g., throttle events: `app/Events/ThrottleOccurred.php` and `app/Listeners/LogAndAlertThrottleEvent.php`).
- Metrics and non-critical integrations must not break main flow — implement safe fallbacks (see `StatsdPublisher` try/catch pattern).
- Prefer using `Http::fake`, `Notification::fake`, and `Bus::fake` in tests to avoid network calls.

Integration and environment notes
---------------------------------
- Google Sheets / HubSpot: client helpers are referenced but API credentials + client wiring may be incomplete; check `app/Services/GoogleSheetsService.php` and `HubSpotService.php` to add the `getGoogleClient()` / `getHubSpotClient()` and required env vars.
- Webhooks: stored in DB (`webhooks` table); `WebhookService::trigger` dispatches `SendWebhookJob` which uses `Http::post` and signs payload if `secret` exists.
- Queue behavior: default connection is `database` (see `config/queue.php`). For production async use `QUEUE_CONNECTION=redis` + Horizon.

How to implement common tasks (examples)
---------------------------------------
- Add a new verification rule:
  1. Add logic to `EmailVerificationService` (or a small helper service and bind it in the provider).
  2. Add unit tests in `tests/Unit` for the service method.
  3. Add integration test that dispatches `VerifyEmailJob` with `QUEUE_CONNECTION=sync` and asserts `VerificationResult` persisted.
- Add a webhook event:
  1. Emit standardized event name from service/job: `app/Services/WebhookService::trigger('verification.completed', $payload)`.
  2. Ensure `webhooks` DB entry exists and test `SendWebhookJob` with `Http::fake()`.
- Mocking network or SMTP: tests should stub `EmailVerificationService` or `CatchAllDetector` (the provider binds singletons; replace using `$this->instance(EmailVerificationService::class, $mock)` in tests).

Testing tips & gotchas
----------------------
- phpunit uses in-memory SQLite and `QUEUE_CONNECTION=sync` — do not rely on external Redis or SMTP in unit tests.
- To assert jobs were queued, use `Bus::fake()` and assert `Bus::assertDispatched(VerifyEmailJob::class)`.
- Use `Notification::fake()` and `Http::fake()` in tests that touch external services.

Repository quirks & housekeeping
-------------------------------
- There is an `apps/` folder with similarly named files — this looks like a scaffold artifact. Prefer `app/` (PSR-4 autoloaded) when making edits and remove/merge `apps/` when fully migrated.
- There is no committed `.env.example` — create one with expected variables (`DB_*, QUEUE_CONNECTION, ADMIN_EMAILS, STATSD_*, GOOGLE_* and HUBSPOT_*`) if you add or depend on env keys.

If you (the human) ask me to make a code change
-----------------------------------------------
- I will: search for references to the target feature, create unit & integration tests first, then implement minimal changes in `app/Services` or `app/Jobs`, run `composer test`, and update docs where needed (this file included).

Questions for reviewers
-----------------------
- Are there intended runtime providers/clients for Google & HubSpot that I should wire to the codebase?
- Should we remove `apps/` or keep it as an upstream artifact?

---

If anything here is unclear or you'd like more examples (test snippets, sample `.env.example`, or a migration for missing columns), tell me which area to expand and I will update this file. ✅
