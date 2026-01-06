# Email Verification Service

A professional-grade Laravel-based REST API service for verifying email addresses with comprehensive validation, bulk processing, integrations, and webhook support.

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 16+ (for Vite assets)
- SQLite, MySQL, or PostgreSQL

### Installation 

```bash
# Clone and setup
git clone https://github.com/ArkadeepSur/Email-verification-service.git
cd Email-verification-service

# Run setup script
composer setup

# Start development environment (requires all services)
composer dev

# Or run services individually:
php artisan serve                           # Server on http://localhost:8000
php artisan queue:work --tries=3           # Queue worker
php artisan pail --timeout=0               # Log viewer
npm run dev                                # Vite development
```

### First Verification

```bash
# Get admin credentials from .env (default: admin@example.com)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Verify a single email
curl -X POST http://localhost:8000/api/verify/single \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com"}'
```

## 📋 Table of Contents

- [Architecture](#architecture)
- [Features](#features)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Configuration](#configuration)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)

---

## 🏗️ Architecture

### Core Verification Pipeline

The service uses a sophisticated 8-step verification pipeline orchestrated by `VerifyEmailJob`:

```
┌─────────────────────────────────────────────────────────────┐
│ Email Verification Pipeline (VerifyEmailJob)                │
└─────────────────────────────────────────────────────────────┘
                              ↓
              1️⃣ Pre-check (Format, Disposable Check)
                              ↓
         2️⃣ Syntax Validation (RFC 5322 Rules)
                              ↓
         3️⃣ MX Records Check (Domain Configuration)
                              ↓
         4️⃣ SMTP Connection Test (Mailbox Existence)
                              ↓
         5️⃣ Catch-All Detection (Domain-level Verification)
                              ↓
         6️⃣ Blacklist Check (Known Bad Domains/Patterns)
                              ↓
         7️⃣ Risk Score Calculation (Multi-factor Assessment)
                              ↓
         8️⃣ Result Persistence (Database Storage)
                              ↓
         9️⃣ Webhook Emission (Integration Triggers)
```

### Key Components

```
app/
├── Jobs/
│   ├── VerifyBulkEmailsJob       # Dispatch multiple emails
│   ├── VerifyEmailJob            # Single email orchestrator
│   └── SendWebhookJob            # Webhook delivery
├── Services/
│   ├── EmailVerificationService  # Verification logic (7 steps)
│   ├── CatchAllDetector          # Domain catch-all detection
│   ├── GoogleSheetsService       # Google Sheets import/export
│   ├── HubSpotService            # HubSpot CRM sync
│   ├── WebhookService            # Webhook orchestration
│   └── Metrics/                  # Observability publishers
├── Models/
│   ├── User                      # User accounts & credits
│   ├── VerificationResult        # Email verification outcomes
│   ├── Webhook                   # Webhook registrations
│   ├── Blacklist                 # Blocked patterns
│   ├── CreditTransaction         # Credit ledger
│   ├── ThrottleEvent             # Rate-limit tracking
│   └── ...
├── Http/
│   ├── Controllers/              # API endpoints
│   └── Middleware/
├── Events/
│   └── ThrottleOccurred          # Rate-limit events
└── Listeners/
    └── LogAndAlertThrottleEvent  # Alert on throttle
```

### Technology Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Laravel 12.44 |
| **API** | REST with Sanctum authentication |
| **Queue** | Database / Redis + Horizon |
| **Database** | Eloquent ORM (SQLite/MySQL/PostgreSQL) |
| **Testing** | PHPUnit 11.5 + Mockery |
| **Code Quality** | Laravel Pint + PHPStan |
| **CI/CD** | GitHub Actions |
| **Integrations** | Google Sheets, HubSpot, Webhooks |
| **Observability** | StatsD metrics, Laravel Pail logs |

---

## ✨ Features

### Email Verification
- ✅ **Syntax Validation** — RFC 5322 format compliance
- ✅ **MX Records** — Domain mail server verification
- ✅ **SMTP Verification** — Mailbox existence testing
- ✅ **Catch-All Detection** — Domain-level validation
- ✅ **Blacklist Checking** — Known bad domains/patterns
- ✅ **Risk Scoring** — Multi-factor risk assessment
- ✅ **Disposable Detection** — Temporary email identification

### API Features
- ✅ **Single Email Verification** — `/verify/single`
- ✅ **Bulk Verification** — `/verify/bulk` (JSON array)
- ✅ **File Upload** — CSV/XLSX file processing
- ✅ **Async Jobs** — Background processing with queue
- ✅ **Status Tracking** — Job progress monitoring
- ✅ **Result Export** — Multiple formats (JSON, CSV)
- ✅ **Credit System** — Per-user quota management

### Integrations
- ✅ **Google Sheets** — Auto-import/export emails
- ✅ **HubSpot CRM** — Contact sync & property updates
- ✅ **Webhooks** — Event-driven integrations (HMAC-signed)
- ✅ **Slack** — Admin notifications on throttle/credits

### Security
- ✅ **API Authentication** — Laravel Sanctum tokens
- ✅ **Rate Limiting** — Per-user and per-IP throttling
- ✅ **Credit Quotas** — Prevent abuse with credit deduction
- ✅ **Webhook Signatures** — HMAC-SHA256 payload signing
- ✅ **Admin Alerts** — Throttle and credit events

---

## 🔌 API Documentation

### Authentication

All endpoints require authentication via `Authorization: Bearer {token}` header (except `/auth/login`).

**Login:**
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}

Response (200):
{
  "token": "5|BnhBfBCpKrI...",
  "user": {
    "id": 1,
    "email": "admin@example.com",
    "credits_balance": 1000
  }
}
```

**Logout:**
```bash
POST /api/auth/logout
Authorization: Bearer {token}

Response (200):
{ "message": "Logged out successfully" }
```

### Verification Endpoints

#### Single Email Verification

**Request:**
```bash
POST /api/verify/single
Authorization: Bearer {token}
Content-Type: application/json

{
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "data": {
    "email": "user@example.com",
    "status": "valid",
    "risk_score": 15,
    "details": {
      "syntax_valid": true,
      "mx_valid": true,
      "smtp_valid": true,
      "catch_all": false,
      "disposable": false,
      "blacklisted": false
    },
    "job_id": "job-uuid-here",
    "verified_at": "2025-12-30T10:30:00Z"
  }
}
```

**Status Values:**
- `valid` — All checks passed; email verified
- `invalid` — Syntax or SMTP check failed
- `catch_all` — Domain accepts all emails (may be valid)
- `disposable` — Temporary/disposable email service
- `unknown` — Unable to determine (missing MX records)

#### Bulk Verification

**Request:**
```bash
POST /api/verify/bulk
Authorization: Bearer {token}
Content-Type: application/json

{
  "emails": [
    "user1@example.com",
    "user2@example.com",
    "user3@example.com"
  ]
}
```

**Response (202 Accepted):**
```json
{
  "data": {
    "job_id": "job-uuid-here",
    "count": 3,
    "status": "queued",
    "estimated_completion": "2025-12-30T10:35:00Z"
  }
}
```

#### File Upload Verification

**Request:**
```bash
POST /api/verify/file
Authorization: Bearer {token}
Content-Type: multipart/form-data

file=@emails.csv (or .xlsx)
```

**Response (202 Accepted):**
```json
{
  "data": {
    "job_id": "job-uuid-here",
    "count": 150,
    "status": "queued"
  }
}
```

#### Job Status

**Request:**
```bash
GET /api/verify/status/{jobId}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "job_id": "job-uuid-here",
    "status": "processing",
    "progress": {
      "total": 100,
      "completed": 45,
      "percentage": 45
    }
  }
}
```

#### Get Results

**Request:**
```bash
GET /api/verify/results/{jobId}?page=1&per_page=20
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "email": "user1@example.com",
      "status": "valid",
      "risk_score": 10,
      "verified_at": "2025-12-30T10:31:00Z"
    },
    {
      "email": "user2@example.com",
      "status": "invalid",
      "risk_score": 95,
      "verified_at": "2025-12-30T10:31:05Z"
    }
  ],
  "meta": {
    "total": 100,
    "per_page": 20,
    "current_page": 1
  }
}
```

#### Export Results

**Request:**
```bash
POST /api/verify/export/{jobId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "format": "csv"  // or "json", "xlsx"
}
```

**Response (200):**
```
CSV file downloaded
```

### Credits Endpoints

#### Check Balance

**Request:**
```bash
GET /api/credits/balance
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "balance": 950,
    "used_today": 50,
    "plan": "professional"
  }
}
```

#### Credit History

**Request:**
```bash
GET /api/credits/history?limit=20
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "type": "verification",
      "amount": -5,
      "balance_after": 950,
      "description": "Verified 5 emails",
      "created_at": "2025-12-30T10:31:00Z"
    }
  ]
}
```

### Blacklist Management

#### List Blacklist Entries

**Request:**
```bash
GET /api/blacklist
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "pattern": "*.temp-mail.com",
      "description": "Temporary email service",
      "is_active": true
    }
  ]
}
```

#### Create Blacklist Entry

**Request:**
```bash
POST /api/blacklist
Authorization: Bearer {token}
Content-Type: application/json

{
  "pattern": "*.invalid-domain.com",
  "description": "Known invalid domain"
}
```

#### Delete Blacklist Entry

**Request:**
```bash
DELETE /api/blacklist/{id}
Authorization: Bearer {token}
```

### Integration Endpoints

#### Google Sheets Sync

**Request:**
```bash
POST /api/integrations/google-sheets/sync
Authorization: Bearer {token}
Content-Type: application/json

{
  "spreadsheet_id": "1aB2cD3eF4gH5iJ6kL7mN8oPq9rStUvWxYz",
  "sheet_name": "Sheet1",
  "range": "A:A"
}
```

**Response (202 Accepted):**
```json
{
  "data": {
    "job_id": "job-uuid-here",
    "count": 250,
    "status": "queued"
  }
}
```

#### HubSpot Sync

**Request:**
```bash
POST /api/integrations/hubspot/sync
Authorization: Bearer {token}
```

**Response (202 Accepted):**
```json
{
  "data": {
    "job_id": "job-uuid-here",
    "count": 500,
    "status": "queued"
  }
}
```

#### Register Webhook

**Request:**
```bash
POST /api/webhooks/register
Authorization: Bearer {token}
Content-Type: application/json

{
  "url": "https://your-domain.com/webhook",
  "event": "verification.completed",
  "secret": "your-secret-key"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "url": "https://your-domain.com/webhook",
    "event": "verification.completed",
    "is_active": true
  }
}
```

### Error Responses

**400 Bad Request:**
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**402 Payment Required:**
```json
{
  "message": "Insufficient credits. Required: 5, Available: 3"
}
```

**429 Too Many Requests:**
```json
{
  "message": "Rate limit exceeded. Retry after 60 seconds."
}
```

**500 Server Error:**
```json
{
  "message": "Server error",
  "error_id": "error-uuid-for-tracking"
}
```

---

## 🗄️ Database Schema

### Users Table

Stores user accounts and credit balances.

```sql
CREATE TABLE users (
  id BIGINT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  credits_balance INT DEFAULT 1000,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**Key Methods:**
- `hasCredits(int $amount): bool` — Check if user has credits
- `deductCredits(int $amount, string $reason): void` — Deduct from balance
- `addCredits(int $amount, string $reason): void` — Add to balance

### Verification Results Table

Stores verification outcomes for each email.

```sql
CREATE TABLE verification_results (
  id BIGINT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  status VARCHAR(50),  -- 'valid', 'invalid', 'catch_all', 'disposable', 'unknown'
  risk_score INT,      -- 0-100 (0=safe, 100=risky)
  details JSON,        -- {"syntax_valid": bool, "mx_valid": bool, ...}
  job_id VARCHAR(255),
  user_id BIGINT FOREIGN KEY,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**Status Values:**
- `valid` — Email verified and safe
- `invalid` — Syntax or SMTP validation failed
- `catch_all` — Domain accepts all emails
- `disposable` — Temporary email service
- `unknown` — Unable to determine

**Risk Score Factors:**
- `+10` — Syntax error
- `+15` — No MX records
- `+30` — SMTP rejection
- `+25` — Catch-all domain
- `+20` — Blacklisted domain
- `+15` — Disposable service

### Webhooks Table

Stores webhook registrations for event triggers.

```sql
CREATE TABLE webhooks (
  id BIGINT PRIMARY KEY,
  user_id BIGINT FOREIGN KEY,
  url VARCHAR(255) NOT NULL,
  event VARCHAR(100),  -- 'verification.completed', 'verification.failed', etc.
  secret VARCHAR(255),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**Webhook Events:**
- `verification.completed` — Email verification finished
- `verification.failed` — Verification process error
- `credits.low` — Balance below threshold
- `subscription.renewed` — Credit renewal

**Webhook Payload Example:**
```json
{
  "event": "verification.completed",
  "timestamp": "2025-12-30T10:31:00Z",
  "data": {
    "email": "user@example.com",
    "status": "valid",
    "risk_score": 15,
    "job_id": "job-uuid"
  }
}
```

**Webhook Signature:**
```
X-Webhook-Signature: sha256=abcd1234...
// Signature = HMAC-SHA256(json_encode($payload), $secret)
```

### Blacklist Table

Stores email patterns and domains to reject.

```sql
CREATE TABLE blacklist (
  id BIGINT PRIMARY KEY,
  pattern VARCHAR(255) UNIQUE NOT NULL,  -- e.g., "*.temp-mail.com"
  description TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Credit Transactions Table

Audit log of all credit operations.

```sql
CREATE TABLE credit_transactions (
  id BIGINT PRIMARY KEY,
  user_id BIGINT FOREIGN KEY,
  type VARCHAR(50),  -- 'verification', 'purchase', 'refund'
  amount INT,        -- Positive or negative
  balance_after INT,
  description VARCHAR(255),
  created_at TIMESTAMP
);
```

### Throttle Events Table

Tracks rate-limiting events for alerting.

```sql
CREATE TABLE throttle_events (
  id BIGINT PRIMARY KEY,
  throttle_key VARCHAR(255),  -- e.g., "user:1:verify"
  email VARCHAR(255),
  ip VARCHAR(45),
  created_at TIMESTAMP
);
```

---

## ⚙️ Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure:

```bash
# App
APP_NAME="Email Verification Service"
APP_ENV=local
APP_KEY=base64:xxxxx
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite           # or mysql, pgsql
DB_DATABASE=database.sqlite
# If using MySQL:
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_USERNAME=root
# DB_PASSWORD=

# Queue
QUEUE_CONNECTION=database      # or redis (production)
REDIS_URL=redis://127.0.0.1:6379

# Cache
CACHE_DRIVER=database          # or redis, file

# Admin Configuration
ADMIN_EMAILS=admin@example.com,support@example.com

# Metrics (Optional)
STATSD_ENABLED=false
STATSD_HOST=localhost
STATSD_PORT=8125
STATSD_PREFIX=email_service

# Google Sheets API
GOOGLE_API_KEY=xxx
GOOGLE_SHEETS_CLIENT_ID=xxx
GOOGLE_SHEETS_CLIENT_SECRET=xxx

# HubSpot
HUBSPOT_API_KEY=xxx

# Slack Notifications (Optional)
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/xxx

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SANCTUM_GUARD=web
```

### Configuration Files

Key configuration files in `config/`:

**`config/verification.php`** — Verification settings:
```php
return [
    'smtp_timeout' => 10,           // SMTP connection timeout
    'smtp_port' => 25,              // SMTP port
    'max_risk_score' => 100,        // Max risk score value
    'catch_all_samples' => 3,       // Random emails for catch-all test
];
```

**`config/throttle.php`** — Rate limiting:
```php
return [
    'per_minute' => 60,              // Requests per minute per user
    'per_hour' => 1000,              // Requests per hour per user
    'alert_threshold' => 0.8,        // Alert at 80% of limit
];
```

**`config/queue.php`** — Queue settings:
```php
return [
    'default' => env('QUEUE_CONNECTION', 'database'),
    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
    ],
];
```

**`config/metrics.php`** — Observability:
```php
return [
    'driver' => env('METRICS_DRIVER', 'null'),
    'drivers' => [
        'null' => NullPublisher::class,
        'statsd' => StatsdPublisher::class,
    ],
];
```

---

## 🛠️ Development

### Project Structure

```
Email-verification-service/
├── app/                      # Application code
│   ├── Http/
│   │   ├── Controllers/      # API endpoints
│   │   ├── Middleware/       # HTTP middleware
│   │   └── Requests/         # Form request validation
│   ├── Jobs/                 # Background jobs
│   ├── Services/             # Business logic
│   ├── Models/               # Eloquent models
│   ├── Events/               # Event classes
│   ├── Listeners/            # Event listeners
│   ├── Notifications/        # Notification classes
│   ├── Providers/            # Service providers
│   └── Console/
│       ├── Kernel.php
│       └── Commands/         # Artisan commands
├── bootstrap/                # Framework bootstrap
├── config/                   # Configuration files
├── database/
│   ├── migrations/           # Database migrations
│   ├── seeders/              # Database seeders
│   └── factories/            # Model factories
├── public/                   # Web root
├── resources/
│   ├── views/                # View templates
│   ├── css/                  # CSS resources
│   └── js/                   # JavaScript resources
├── routes/
│   ├── api.php              # API routes
│   └── web.php              # Web routes
├── storage/                  # Runtime files (logs, uploads)
├── tests/                    # Test suites
│   ├── Unit/                 # Unit tests
│   ├── Feature/              # Feature tests
│   └── TestCase.php          # Base test class
├── vendor/                   # Composer dependencies
├── .env.example              # Environment template
├── composer.json             # PHP dependencies
├── package.json              # Node dependencies
├── phpunit.xml               # PHPUnit configuration
├── phpstan.neon              # PHPStan configuration
└── vite.config.js            # Vite configuration
```

### Composer Scripts

```bash
composer install              # Install dependencies
composer setup                # Install + migrate + build
composer dev                  # Start development environment
composer test                 # Run tests + Pint + PHPStan
composer test:unit            # Unit tests only
composer test:feature         # Feature tests only
```

### Artisan Commands

```bash
# Database
php artisan migrate           # Run migrations
php artisan migrate:fresh     # Fresh migration
php artisan seed:run          # Run seeders
php artisan seed:run --class=DevSeeder

# Queue
php artisan queue:work        # Start queue worker
php artisan queue:failed      # List failed jobs
php artisan queue:retry       # Retry failed jobs

# Cache & Config
php artisan config:clear      # Clear config cache
php artisan cache:clear       # Clear application cache
php artisan storage:link      # Create storage symlink

# Code Quality
php artisan tinker            # Interactive REPL
php artisan migrate:status    # Check migration status
```

### Adding a New Verification Rule

1. **Add to EmailVerificationService:**
   ```php
   public function myCustomCheck(string $email): bool
   {
       // Implementation
   }
   ```

2. **Call in VerifyEmailJob:**
   ```php
   $details['my_custom_check'] = $this->service->myCustomCheck($email);
   ```

3. **Update risk scoring:**
   ```php
   if (!$details['my_custom_check']) {
       $riskScore += 20;
   }
   ```

4. **Add unit test:**
   ```php
   public function test_my_custom_check(): void
   {
       $service = app(EmailVerificationService::class);
       $result = $service->myCustomCheck('test@example.com');
       $this->assertTrue($result);
   }
   ```

---

## 🧪 Testing

### Running Tests

```bash
# All tests
composer test

# Unit tests only
php artisan test tests/Unit

# Feature tests only
php artisan test tests/Feature

# Specific test class
php artisan test tests/Unit/EmailVerificationServiceTest

# Specific test method
php artisan test tests/Unit/EmailVerificationServiceTest --filter=test_validate_syntax

# With coverage
php artisan test --coverage

# Watch mode (requires PestPHP or similar)
php artisan test --watch
```

### Test Structure

**Unit Tests** (`tests/Unit/`):
- Test individual services
- No database access
- Mock external dependencies
- Fast execution

**Feature Tests** (`tests/Feature/`):
- Test complete workflows
- Use in-memory SQLite database
- Test job processing with `QUEUE_CONNECTION=sync`
- Use `Bus::fake()`, `Http::fake()`, `Notification::fake()`

### Example Test

```php
<?php

namespace Tests\Feature;

use App\Jobs\VerifyEmailJob;
use App\Models\VerificationResult;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class VerifyEmailJobTest extends TestCase
{
    public function test_handle_persists_result(): void
    {
        $job = new VerifyEmailJob('test@example.com', 1);
        $job->handle();

        $this->assertDatabaseHas('verification_results', [
            'email' => 'test@example.com',
            'user_id' => 1,
        ]);
    }
}
```

### Test Gotchas

- ✅ Tests use `QUEUE_CONNECTION=sync` (synchronous execution)
- ✅ Tests use in-memory SQLite (fast, clean state)
- ✅ Mock external API calls with `Http::fake()`
- ✅ Mock notifications with `Notification::fake()`
- ✅ Mock queued jobs with `Bus::fake()`
- ❌ Don't make real network calls in tests
- ❌ Don't depend on `.env` file in tests

---

## 🚀 Deployment

### Production Checklist

- [ ] Copy `.env.example` → `.env` and configure for production
- [ ] Generate application key: `php artisan key:generate`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Install composer with `--no-dev` flag
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed data if needed: `php artisan seed:run --force`
- [ ] Clear config cache: `php artisan config:cache`
- [ ] Clear application cache: `php artisan cache:clear`
- [ ] Set up queue worker (supervisor or similar)
- [ ] Set up log rotation and monitoring
- [ ] Enable HTTPS
- [ ] Configure CORS properly
- [ ] Set up database backups
- [ ] Configure external integrations (Google, HubSpot, etc.)

### Queue Setup (Production)

**Using Redis + Horizon:**

1. Install Horizon:
   ```bash
   composer require laravel/horizon
   php artisan horizon:install
   ```

2. Configure `config/horizon.php`

3. Set `QUEUE_CONNECTION=redis` in `.env`

4. Start Horizon:
   ```bash
   php artisan horizon
   ```

**Using Supervisor:**

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/logs/worker.log
```

### Monitoring

- **Queue Health**: Monitor `jobs` table for stuck jobs
- **Credit System**: Alert when users low on credits
- **Throttling**: Alert on rate-limit threshold breach
- **Error Tracking**: Use Sentry or similar for error reporting
- **Performance**: Monitor verification times and success rates
- **Logs**: Use Laravel Pail or ELK for log aggregation

---

## 🔧 Troubleshooting

### Queue Not Processing

**Issue**: Jobs stuck in queue

**Solutions**:
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Restart queue worker
php artisan queue:work --tries=3
```

### Database Locked (SQLite)

**Issue**: "database is locked" error

**Solutions**:
```bash
# Use MySQL or PostgreSQL for production
# OR increase lock timeout in config/database.php
'sqlite' => [
    'timeout' => 20,
],
```

### SMTP Connection Failed

**Issue**: Email verification fails

**Solutions**:
- Check SMTP server is accessible on port 25, 465, or 587
- Check firewall rules
- Verify `config/verification.php` SMTP settings
- Enable debug logging to see SMTP details

### Webhooks Not Delivering

**Issue**: Webhooks not reaching endpoint

**Solutions**:
```bash
# Check webhook logs
php artisan logs | grep -i webhook

# Verify webhook URL is accessible
curl -X POST https://your-endpoint.com/webhook

# Check webhook secret in database
SELECT * FROM webhooks;

# Re-register webhook with active=true
```

### Out of Memory

**Issue**: "Allowed memory size exhausted"

**Solutions**:
```php
// Increase in php.ini or .env
php_value memory_limit 512M

// Or batch process in commands/jobs
$emails = collect($emails)->chunk(1000);
```

### CORS Errors

**Issue**: "No 'Access-Control-Allow-Origin' header"

**Solutions**:
```env
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
SESSION_DOMAIN=yourdomain.com
```

---

## 📚 Contributing

### Code Standards

- Follow **Laravel best practices**
- Use **Laravel Pint** for code style: `vendor/bin/pint`
- Use **PHPStan** for static analysis: `vendor/bin/phpstan`
- Write **PHPUnit tests** for all features
- Add **docblocks** to classes and methods
- Use **meaningful variable names**

### Workflow

1. **Create feature branch**:
   ```bash
   git checkout -b feature/my-feature
   ```

2. **Write tests first**:
   ```bash
   php artisan test
   ```

3. **Implement feature** in `app/Services/` or `app/Jobs/`

4. **Run code quality checks**:
   ```bash
   vendor/bin/pint --test
   vendor/bin/phpstan analyse -c phpstan.neon
   composer test
   ```

5. **Commit with semantic message**:
   ```bash
   git commit -m "feat: add email verification cache"
   ```

6. **Push and create PR**:
   ```bash
   git push origin feature/my-feature
   ```

### Commit Message Convention

```
feat:    New feature
fix:     Bug fix
docs:    Documentation
style:   Code style (Pint fixes)
refactor: Code refactoring
perf:    Performance improvement
test:    Test additions
chore:   Dependencies, setup
```

Example:
```
feat: add webhook retry mechanism
fix: correct SMTP timeout calculation
docs: update API documentation
```

### Pull Request Template

```markdown
## Description
Describe what this PR does.

## Type of Change
- [ ] New feature
- [ ] Bug fix
- [ ] Documentation
- [ ] Code refactor

## Testing
Describe how to test this change.

## Checklist
- [ ] Tests pass (`composer test`)
- [ ] Pint passes (`vendor/bin/pint --test`)
- [ ] PHPStan passes (`vendor/bin/phpstan`)
- [ ] Documentation updated
- [ ] Commit messages follow convention
```

---

## 📞 Support

- **Issues**: GitHub Issues
- **Documentation**: See files in `docs/`
- **API Docs**: See [API Documentation](#-api-documentation) section
- **Contributing**: See [Contributing](#-contributing) section

---

## 📄 License

MIT License. See [LICENSE](LICENSE) file.

---

## 🙏 Acknowledgments

Built with:
- [Laravel Framework](https://laravel.com)
- [Sanctum](https://github.com/laravel/sanctum)
- [PHPUnit](https://phpunit.de)
- [Laravel Pint](https://github.com/laravel/pint)
- [PHPStan](https://phpstan.org)

---

**Last Updated**: December 30, 2025  
**Version**: 1.0.0  
**Status**: Production Ready
