# PowerShell script to clear Laravel caches
# This script uses the full path to PHP from Laragon

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Clearing Laravel Caches" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Set the PHP path
$phpPath = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$projectPath = "C:\laragon\www\stmarksms"

# Check if PHP exists
if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP not found at $phpPath" -ForegroundColor Red
    Write-Host "Please check your Laragon installation." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "Using PHP: $phpPath" -ForegroundColor Green
Write-Host ""

# Change to project directory
Set-Location $projectPath

# Step 1: Clear application cache
Write-Host "Step 1: Clearing application cache..." -ForegroundColor Yellow
& $phpPath artisan cache:clear
Write-Host "✓ Cache cleared" -ForegroundColor Green
Write-Host ""

# Step 2: Clear config cache
Write-Host "Step 2: Clearing config cache..." -ForegroundColor Yellow
& $phpPath artisan config:clear
Write-Host "✓ Config cleared" -ForegroundColor Green
Write-Host ""

# Step 3: Clear view cache
Write-Host "Step 3: Clearing view cache..." -ForegroundColor Yellow
& $phpPath artisan view:clear
Write-Host "✓ View cleared" -ForegroundColor Green
Write-Host ""

# Step 4: Clear route cache
Write-Host "Step 4: Clearing route cache..." -ForegroundColor Yellow
& $phpPath artisan route:clear
Write-Host "✓ Route cleared" -ForegroundColor Green
Write-Host ""

Write-Host "========================================" -ForegroundColor Green
Write-Host "✓ All caches cleared successfully!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Refresh your browser (Ctrl+F5)" -ForegroundColor White
Write-Host "2. Go to HR → Contract Management" -ForegroundColor White
Write-Host "3. You should now see the contract feature fixes" -ForegroundColor White
Write-Host ""
Read-Host "Press Enter to exit"
