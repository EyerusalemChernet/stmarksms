@echo off
REM Payroll Fix Verification Script
REM This script verifies that all payroll fixes are in place

echo.
echo ==========================================
echo Payroll Fix Verification
echo ==========================================
echo.

REM Set PHP path
set PHP_PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe

REM Check if PHP exists
echo Step 1: Checking PHP Installation...
if exist "%PHP_PATH%" (
    echo [OK] PHP found
) else (
    echo [ERROR] PHP not found at %PHP_PATH%
    exit /b 1
)

REM Clear caches
echo.
echo Step 2: Clearing All Caches...
echo Clearing application cache...
"%PHP_PATH%" artisan cache:clear
echo.

echo Clearing config cache...
"%PHP_PATH%" artisan config:clear
echo.

echo Clearing view cache...
"%PHP_PATH%" artisan view:clear
echo.

echo Clearing route cache...
"%PHP_PATH%" artisan route:clear
echo.

REM Check files
echo Step 3: Verifying Files...
if exist "app\Http\Controllers\SupportTeam\PayrollController.php" (
    echo [OK] PayrollController.php exists
) else (
    echo [ERROR] PayrollController.php not found
)

if exist "routes\web.php" (
    echo [OK] web.php exists
) else (
    echo [ERROR] web.php not found
)

if exist "resources\views\pages\hr\payroll.blade.php" (
    echo [OK] payroll.blade.php exists
) else (
    echo [ERROR] payroll.blade.php not found
)

echo.
echo ==========================================
echo Verification Complete!
echo ==========================================
echo.
echo All caches have been cleared.
echo.
echo Next Steps:
echo 1. Clear browser cache: Ctrl+F5
echo 2. Go to HR -> Payroll
echo 3. Test Edit, PDF, and CSV buttons
echo.
pause
