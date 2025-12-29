# Safe PowerShell helper to scaffold a Laravel app and migrate existing `apps/` code
# Run from repository root in PowerShell (Run as normal user)

param(
    [switch]$DryRun
)

function Require-Command($name) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        Write-Error "$name must be installed and on PATH. Aborting."
        exit 1
    }
}

Require-Command composer

Write-Host "About to scaffold Laravel using 'composer create-project laravel/laravel .'"
if ($DryRun) {
    Write-Host "Dry run mode -- no changes will be made."
    exit 0
}

# Create Laravel skeleton
composer create-project laravel/laravel . --no-interaction
if ($LASTEXITCODE -ne 0) { Write-Error 'composer create-project failed'; exit 1 }

# Move existing `apps/` files into `app/` (back up first)
if (Test-Path apps) {
    $timestamp = Get-Date -Format 'yyyyMMddHHmmss'
    $backup = "apps_backup_$timestamp"
    Write-Host "Backing up existing 'apps/' to './$backup'"
    Rename-Item -Path apps -NewName $backup

    Write-Host "Moving backup into Laravel 'app/' directory"
    Get-ChildItem $backup | ForEach-Object {
        Move-Item -Path $_.FullName -Destination app -Force
    }

    Write-Host "You should now open files and verify namespaces (should be 'App\\...') and run 'composer dump-autoload'"
}

Write-Host "Scaffold complete. Next steps: copy .env.example to .env and update settings, run 'composer install', 'php artisan key:generate', 'php artisan migrate'"
Write-Host "If you plan to use queues and Redis, install and configure Redis and Horizon (composer require laravel/horizon)."
