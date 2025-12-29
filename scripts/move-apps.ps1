<#
Simple safe script to move top-level items from `apps/` into `app/`.
Behavior:
 - Creates a timestamped backup of any conflicting `app/` items under `scripts/backups/`
 - Moves contents from `apps/` -> `app/`
 - Optional flags:
    -Force: overwrite existing `app/` items (backs them up first)
    -UpdateNamespaces: replace PHP namespaces that start with `apps\` or `Apps\` -> `App\` (applies only to moved files)
    -RunComposer: run `composer dump-autoload` after moving

Usage examples:
  # Dry-run (default: will perform actual move but backs up conflicts)
  .\move-apps.ps1
  # Force overwrite
  .\move-apps.ps1 -Force
  # Update namespaces and re-generate autoload
  .\move-apps.ps1 -UpdateNamespaces -RunComposer
#>

param(
    [switch]$Force,
    [switch]$UpdateNamespaces,
    [switch]$RunComposer
)

# Resolve repository root as the parent of the script directory (scripts/ is inside repo root)
$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

$appsPath = Join-Path $repoRoot 'apps'
$appPath = Join-Path $repoRoot 'app'
$backupRoot = Join-Path $repoRoot 'scripts\backups'

if (!(Test-Path $appsPath)) {
    Write-Host "No 'apps/' dir found; nothing to do." -ForegroundColor Yellow
    exit 0
}

$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$backupDir = Join-Path $backupRoot "apps_backup_$timestamp"
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null

Get-ChildItem -Path $appsPath -Force | ForEach-Object {
    $name = $_.Name
    $src = $_.FullName
    $dest = Join-Path $appPath $name

    if (Test-Path $dest) {
        Write-Host "Conflict: 'app/$name' exists." -ForegroundColor Yellow
        # Backup existing destination
        $destBackup = Join-Path $backupDir $name
        Write-Host "Backing up existing 'app/$name' to '$destBackup'..."
        Move-Item -Path $dest -Destination $destBackup -Force
    }

    Write-Host "Moving 'apps/$name' -> 'app/$name'..."
    Move-Item -Path $src -Destination $appPath -Force:$Force
}

# Optionally update namespaces in moved PHP files
if ($UpdateNamespaces) {
    Write-Host "Updating PHP namespaces in moved files (apps\ -> App\)..." -ForegroundColor Cyan
    Get-ChildItem -Path $appPath -Recurse -Filter *.php | ForEach-Object {
        $file = $_.FullName
        $content = Get-Content -Raw -Path $file
        $new = $content -replace "namespace\s+apps\\([A-Za-z0-9_\\]+);","namespace App\\$1;"
        $new = $new -replace "namespace\s+Apps\\([A-Za-z0-9_\\]+);","namespace App\\$1;"
        $new = $new -replace "namespace\s+apps;","namespace App;"
        $new = $new -replace "namespace\s+Apps;","namespace App;"
        if ($new -ne $content) {
            Set-Content -Path $file -Value $new -Force
            Write-Host "Updated namespace in: $file"
        }
    }
}

if ($RunComposer) {
    if (Get-Command composer -ErrorAction SilentlyContinue) {
        Write-Host "Running 'composer dump-autoload'..." -ForegroundColor Cyan
        composer dump-autoload
    } else {
        Write-Host "Composer not found in PATH. Skipping dump-autoload." -ForegroundColor Yellow
    }
}

Write-Host "Move complete. Backups are at: $backupDir" -ForegroundColor Green
Write-Host "Next steps: review moved files, run 'composer dump-autoload' if not done, and run tests." -ForegroundColor Green
