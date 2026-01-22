# Deployment Guide for Email Verification Service

## Quick Start - Railway Deployment

### Prerequisites
- Railway Account (https://railway.app)
- Git repository pushed to GitHub

### Step 1: Set up Environment Variables on Railway

Copy these environment variables to your Railway project settings:

```bash
# Core Application
APP_NAME="Email Verification Service"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-railway-domain.railway.app

# Database (Railway MySQL Plugin)
DB_CONNECTION=mysql
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=<railway-mysql-password>

# Sessions & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail (use log for testing, configure SMTP for production)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Email Verification"

# Admin Configuration
ADMIN_EMAILS=admin@example.com

# Metrics (optional)
METRICS_DRIVER=null

# CORS for API requests
CORS_ALLOWED_ORIGINS=https://your-domain.com
SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

**Important:** Add these via Railway Dashboard → Your Project → Variables (not in Dockerfile)

### Step 2: Deploy to Railway

1. **Connect Repository**
   - Go to Railway.app
   - Click "New Project"
   - Select "Deploy from GitHub"
   - Authorize GitHub and select this repository

2. **Add Database**
   - Click "+ Create" in Railway project
   - Select "MySQL"
   - Railway will automatically detect migrations and run them

3. **Configure Variables**
   - Copy the MySQL connection string
   - Add all environment variables from Step 1
   - Railway should auto-generate `APP_KEY`

4. **Deploy**
   - Push to GitHub (Railway auto-deploys on push)
   - Monitor deployment logs in Railway Dashboard

### Step 3: Verify Deployment

After deployment:
1. Visit your Railway domain
2. Test registration: `/register`
3. Test login with default admin: `/login` (if seeded)
4. Check logs in Railway for any errors

---

## Troubleshooting 500 Errors

### Error 1: "Base table or view already exists"
**Cause:** Migrations were partially run
**Solution:** Already handled by idempotent migrations (table existence checks)

### Error 2: "Key column 'user_id' doesn't exist"
**Cause:** Migration order issue with column additions
**Solution:** Already handled by conditional index creation

### Error 3: "Target class [xyz] does not exist"
**Cause:** Laravel app not bootstrapped properly
**Solutions:**
```bash
# Run these on Railway terminal or via bash hook:
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

### Error 4: "SQLSTATE connection refused"
**Cause:** Database not connected
**Solution:** Verify `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` in Environment Variables

---

## Local Testing Before Deployment

### 1. Set up local environment
```bash
cp .env.example .env
php artisan key:generate
```

### 2. Configure database (MySQL)
```bash
# Update .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=email_verification
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Run migrations and seed
```bash
php artisan migrate
php artisan db:seed --class=DevSeeder
```

### 4. Test registration
```bash
composer dev
# Visit http://localhost:8000/register
```

---

## Production Checklist

- [ ] APP_DEBUG=false (never true in production)
- [ ] APP_ENV=production
- [ ] APP_KEY generated and set
- [ ] Database migrations completed successfully
- [ ] CSRF tokens working in forms
- [ ] Email configuration correct (SMTP or logs)
- [ ] Admin emails configured
- [ ] CORS origins configured for your domain
- [ ] Logs accessible for debugging
- [ ] Backups configured for database

---

## Common Issues & Fixes

### Registration Returns 500
1. Check Railway logs: `railway logs`
2. Verify database connection:
   ```bash
   php artisan tinker
   DB::connection()->getPdo(); # should not error
   ```
3. Check migrations ran:
   ```bash
   php artisan migrate:status
   ```

### Users Table Not Found
```bash
# Re-run migrations
php artisan migrate:refresh
# Or if data exists:
php artisan migrate --force
```

### Queue Jobs Not Processing
For webhook deliveries and background jobs:
```bash
# If using database queue (default):
php artisan queue:work --daemon

# For production, use a queue manager:
# - Horizon (Redis-backed)
# - Or configure to use external service
```

---

## Environment Variables Reference

| Variable | Purpose | Example |
|----------|---------|---------|
| `APP_KEY` | Encryption key (auto-generated) | base64:xxx |
| `APP_ENV` | Environment mode | production |
| `APP_DEBUG` | Debug mode | false |
| `DB_*` | Database connection | MySQL |
| `MAIL_*` | Email configuration | SMTP or log |
| `ADMIN_EMAILS` | Comma-separated admin emails | admin@example.com |
| `QUEUE_CONNECTION` | Job queue driver | database |
| `SESSION_DRIVER` | Session storage | database |
| `CACHE_STORE` | Cache driver | database |

---

## Need Help?

- **Railway Docs:** https://docs.railway.app
- **Laravel Docs:** https://laravel.com/docs
- **Check logs:** `railway logs` or Railway Dashboard → Logs tab
- **SSH to container:** `railway shell` (if available)
