# Setup script for Employee Qualification Upload Feature
# Run this from PowerShell in the project directory

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "HR System - Qualification Upload Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if we're in the right directory
if (-not (Test-Path "artisan")) {
    Write-Host "ERROR: artisan file not found." -ForegroundColor Red
    Write-Host "Please run this from the project root directory." -ForegroundColor Red
    Write-Host "Current directory: $(Get-Location)" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "Step 1: Running database migrations..." -ForegroundColor Yellow
php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Migrations completed" -ForegroundColor Green
} else {
    Write-Host "WARNING: Migration may have failed. Check the output above." -ForegroundColor Yellow
}
Write-Host ""

Write-Host "Step 2: Clearing application caches..." -ForegroundColor Yellow
php artisan cache:clear
Write-Host "✓ Cache cleared" -ForegroundColor Green
php artisan config:clear
Write-Host "✓ Config cleared" -ForegroundColor Green
php artisan view:clear
Write-Host "✓ View cleared" -ForegroundColor Green
php artisan route:clear
Write-Host "✓ Route cleared" -ForegroundColor Green
Write-Host ""

Write-Host "Step 3: Setting up storage link..." -ForegroundColor Yellow
if (Test-Path "public\storage") {
    Write-Host "✓ Storage link already exists" -ForegroundColor Green
} else {
    php artisan storage:link
    Write-Host "✓ Storage link created" -ForegroundColor Green
}
Write-Host ""

Write-Host "Step 4: Creating qualifications directory..." -ForegroundColor Yellow
if (-not (Test-Path "storage\app\public\qualifications")) {
    New-Item -ItemType Directory -Path "storage\app\public\qualifications" -Force | Out-Null
    Write-Host "✓ Qualifications directory created" -ForegroundColor Green
} else {
    Write-Host "✓ Qualifications directory exists" -ForegroundColor Green
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Green
Write-Host "✓ Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Refresh your browser (Ctrl+F5)" -ForegroundColor White
Write-Host "2. Go to HR Module > Employees" -ForegroundColor White
Write-Host "3. Select an employee and click 'Edit Profile'" -ForegroundColor White
Write-Host "4. Scroll to 'Qualifications' section" -ForegroundColor White
Write-Host "5. Upload a certificate file" -ForegroundColor White
Write-Host "6. Click 'Save Changes'" -ForegroundColor White
Write-Host "7. Go back to employee profile to verify" -ForegroundColor White
Write-Host ""
Write-Host "For more information, see: QUALIFICATION_UPLOAD_GUIDE.md" -ForegroundColor Cyan
Write-Host ""
Read-Host "Press Enter to exit"
