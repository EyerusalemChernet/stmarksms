# Aggressive Cache Clear Script for Laravel
# This script clears ALL caches and compiled files

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                                                                ║" -ForegroundColor Cyan
Write-Host "║           AGGRESSIVE CACHE CLEAR - LARAVEL                    ║" -ForegroundColor Cyan
Write-Host "║                                                                ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$PHP_PATH = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"

Write-Host "[1/8] Clearing application cache..." -ForegroundColor Yellow
& $PHP_PATH artisan cache:clear
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: Failed to clear application cache" -ForegroundColor Red }

Write-Host "[2/8] Clearing config cache..." -ForegroundColor Yellow
& $PHP_PATH artisan config:clear
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: Failed to clear config cache" -ForegroundColor Red }

Write-Host "[3/8] Clearing view cache..." -ForegroundColor Yellow
& $PHP_PATH artisan view:clear
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: Failed to clear view cache" -ForegroundColor Red }

Write-Host "[4/8] Clearing route cache..." -ForegroundColor Yellow
& $PHP_PATH artisan route:clear
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: Failed to clear route cache" -ForegroundColor Red }

Write-Host "[5/8] Clearing compiled classes..." -ForegroundColor Yellow
& $PHP_PATH artisan optimize:clear
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: Failed to clear compiled classes" -ForegroundColor Red }

Write-Host "[6/8] Deleting bootstrap cache files..." -ForegroundColor Yellow
if (Test-Path "bootstrap\cache\*.php") {
    Remove-Item "bootstrap\cache\*.php" -Force
    Write-Host "    ✓ Bootstrap cache cleared" -ForegroundColor Green
} else {
    Write-Host "    ✓ Bootstrap cache already empty" -ForegroundColor Green
}

Write-Host "[7/8] Deleting storage framework cache..." -ForegroundColor Yellow
if (Test-Path "storage\framework\cache") {
    Remove-Item "storage\framework\cache" -Recurse -Force -ErrorAction SilentlyContinue
    New-Item -ItemType Directory -Path "storage\framework\cache" -Force | Out-Null
    Write-Host "    ✓ Storage cache cleared" -ForegroundColor Green
} else {
    Write-Host "    ✓ Storage cache already empty" -ForegroundColor Green
}

Write-Host "[8/8] Deleting storage framework views..." -ForegroundColor Yellow
if (Test-Path "storage\framework\views") {
    Remove-Item "storage\framework\views" -Recurse -Force -ErrorAction SilentlyContinue
    New-Item -ItemType Directory -Path "storage\framework\views" -Force | Out-Null
    Write-Host "    ✓ Storage views cleared" -ForegroundColor Green
} else {
    Write-Host "    ✓ Storage views already empty" -ForegroundColor Green
}

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                                                                ║" -ForegroundColor Green
Write-Host "║              ✅ ALL CACHES CLEARED SUCCESSFULLY               ║" -ForegroundColor Green
Write-Host "║                                                                ║" -ForegroundColor Green
Write-Host "║  NEXT STEPS:                                                   ║" -ForegroundColor Green
Write-Host "║  1. Restart your Laravel development server                   ║" -ForegroundColor Green
Write-Host "║  2. Press Ctrl+F5 in your browser                             ║" -ForegroundColor Green
Write-Host "║  3. Navigate to HR → Contract Management                      ║" -ForegroundColor Green
Write-Host "║                                                                ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

Read-Host "Press Enter to exit"
