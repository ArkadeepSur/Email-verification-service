Scaffold a Laravel application (Windows PowerShell)

Overview
--------
This repository contains PHP service code in `apps/` that follows Laravel conventions (jobs, services, routes). To fully scaffold a canonical Laravel application and run it locally, follow the steps below.

Pre-requisites
--------------
- PHP 8.1+ installed and on PATH
- Composer installed and on PATH
- MySQL / Postgres (or other DB) for migrations
- Redis (for queues) if you plan to use Horizon/Redis queues
- Optional: Docker and docker-compose for containerized development

Scaffold steps (safe, Windows PowerShell)
----------------------------------------
1. Back up the repository or ensure changes are committed.
2. From the repository root run (PowerShell):

   # Create a fresh Laravel skeleton in the current directory
   composer create-project laravel/laravel . --no-interaction

   # Note: This will populate the repository with Laravel application files.

3. Move existing `apps/` code into Laravel structure (review namespace and autoloading after moving):

   # Example PowerShell commands (run after verifying skeleton):
   if (Test-Path apps) {
       if (!(Test-Path app)) { New-Item -ItemType Directory -Path app }
       Get-ChildItem apps | ForEach-Object { Move-Item $_.FullName -Destination app -Force }
   }

4. Check namespaces and PSR-4 autoloading:
   - Laravel expects app classes under `App\` namespace in `app/`.
   - Adjust class namespaces in moved files (e.g., `namespace App\Services;`) if necessary.
   - Run: composer dump-autoload

5. Environment and packages:
   - Copy `.env.example` (created below) to `.env` and update DB/Redis/third-party credentials.
   - Install packages used by the code (examples):
     composer require laravel/sanctum
     composer require laravel/horizon
     composer require guzzlehttp/guzzle

6. Migrate & run
   php artisan key:generate
   php artisan migrate
   php artisan horizon & (or Start Horizon via supervisor/Docker)
   php artisan serve --host=127.0.0.1 --port=8000

Post-scaffold checklist
-----------------------
- Verify routes: move `apps/routes/api.php` -> `routes/api.php` and ensure controllers exist and namespaces are correct.
- Register any service providers or bindings for `CatchAllDetector`, `GoogleSheetsService`, `HubSpotService`, `WebhookService`.
- Configure queues: set `QUEUE_CONNECTION=redis` and start workers.
- Set up Google API credentials and HubSpot API keys in `.env`.
- Add Sanctum configuration for API token auth if needed.

If you want, I can:
- Create a PowerShell helper script to perform the safe steps above.
- Create a `.env.example` file with recommended keys.
- Help move `apps/` files into `app/`, update namespaces, and register services.

If you prefer, I can proceed to add the helper script and `.env.example` now.

