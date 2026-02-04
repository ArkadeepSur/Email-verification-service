<!-- Combined documentation generated from multiple repo markdown files -->
# Combined Documentation

This file aggregates all repository markdown documentation for centralized reference.

**Source files included:**
- .github/copilot-instructions.md
- DEPLOYMENT.md
- LARAVEL_CLOUD_TROUBLESHOOTING.md
- MEDIUM_PRIORITY_FIXES.md
- PROJECT_STATUS.md
- README.md
- README_SCAFFOLD_NOTES.md
- SCaffolding_README.md

**Last compiled:** January 29, 2026

---

## 🚀 DEPLOYMENT QUICKSTART

### Critical Setup Before Deployment

```bash
# 1. Create .env from template (DO NOT commit .env to repo)
cp .env.example .env

# 2. Generate APP_KEY (required for encryption)
php artisan key:generate

# 3. Configure database (edit .env with production DB details)
# DB_CONNECTION=mysql
# DB_HOST=your-db-host.com
# DB_DATABASE=production_db
# DB_USERNAME=db_user
# DB_PASSWORD=strong_password_here

# 4. Run migrations
php artisan migrate --force

# 5. Build assets (if using Vite)
npm run build

# 6. Start queue worker (for background jobs)
php artisan queue:work

# 7. For production with Horizon (Redis)
php artisan horizon  # or use supervisor config
```

### Environment Variables to Rotate/Set in Production
- `APP_KEY` — Auto-generated, do not share
- `APP_DEBUG` — Must be `false` in production
- `APP_ENV` — Must be `production`
- `DB_PASSWORD` — Use strong password, rotate regularly
- `SANCTUM_STATEFUL_DOMAINS` — Set to your domain
- `ADMIN_EMAILS` — Configure for your team

### Security Checklist
- ✅ `.env` file removed from repo (only `.env.example` present)
- ✅ `/dev/login` endpoint guarded by `APP_ENV=local` check
- ✅ Storage directories excluded from git via `.gitignore`
- ✅ Secrets rotated in production deployment
- ✅ HTTPS enforced on webhook URLs (production only)

---

## Summary

This combined documentation file merges all 8 markdown files from the repository into one central reference document for easier navigation and search.

## Quick Navigation

### Setup & Configuration
- See "Scaffolding README" for initial project setup
- See "Scaffold Notes" for post-scaffolding configuration
- See "Deployment Guide" for detailed production setup on Railway

### Development
- See "Copilot Instructions" for development workflows and code patterns
- See "Main README" for full API documentation and architecture
- See "Project Status" for completed work and current state

### Troubleshooting & Maintenance
- See "Laravel Cloud Troubleshooting" for common errors and solutions
- See "Deployment Guide" for production issues
- See "Medium-Priority Fixes" for performance improvements and code quality

---

## Individual File References

Each major section below corresponds to one of the original markdown files. Original formatting and structure have been preserved for easy reference.

---
