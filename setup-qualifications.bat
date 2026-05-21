@echo off
REM Setup script for Employee Qualification Upload Feature
REM Run this from Command Prompt in the project directory

echo.
echo ========================================
echo HR System - Qualification Upload Setup
echo ========================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo ERROR: artisan file not found. Please run this from the project root directory.
    echo Current directory: %cd%
    pause
    exit /b 1
)

echo Step 1: Running database migrations...
php artisan migrate --force
if errorlevel 1 (
    echo WARNING: Migration may have failed. Check the output above.
) else (
    echo ✓ Migrations completed
)
echo.

echo Step 2: Clearing application caches...
php artisan cache:clear
echo ✓ Cache cleared
php artisan config:clear
echo ✓ Config cleared
php artisan view:clear
echo ✓ View cleared
php artisan route:clear
echo ✓ Route cleared
echo.

echo Step 3: Setting up storage link...
if exist "public\storage" (
    echo ✓ Storage link already exists
) else (
    php artisan storage:link
    echo ✓ Storage link created
)
echo.

echo Step 4: Creating qualifications directory...
if not exist "storage\app\public\qualifications" (
    mkdir storage\app\public\qualifications
    echo ✓ Qualifications directory created
) else (
    echo ✓ Qualifications directory exists
)
echo.

echo ========================================
echo ✓ Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Refresh your browser (Ctrl+F5)
echo 2. Go to HR Module ^> Employees
echo 3. Select an employee and click "Edit Profile"
echo 4. Scroll to "Qualifications" section
echo 5. Upload a certificate file
echo 6. Click "Save Changes"
echo 7. Go back to employee profile to verify
echo.
echo For more information, see: QUALIFICATION_UPLOAD_GUIDE.md
echo.
pause
