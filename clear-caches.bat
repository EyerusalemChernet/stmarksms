@echo off
REM Clear all Laravel caches to apply contract feature fixes
REM This script uses the full path to PHP from Laragon

echo.
echo ========================================
echo Clearing Laravel Caches
echo ========================================
echo.

REM Set the PHP path
set PHP_PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set PROJECT_PATH=C:\laragon\www\stmarksms

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please check your Laragon installation.
    pause
    exit /b 1
)

echo Using PHP: %PHP_PATH%
echo.

REM Change to project directory
cd /d "%PROJECT_PATH%"

echo Step 1: Clearing application cache...
"%PHP_PATH%" artisan cache:clear
echo.

echo Step 2: Clearing config cache...
"%PHP_PATH%" artisan config:clear
echo.

echo Step 3: Clearing view cache...
"%PHP_PATH%" artisan view:clear
echo.

echo Step 4: Clearing route cache...
"%PHP_PATH%" artisan route:clear
echo.

echo ========================================
echo All caches cleared successfully!
echo ========================================
echo.
echo Next steps:
echo 1. Refresh your browser (Ctrl+F5)
echo 2. Go to HR ^> Contract Management
echo 3. You should now see the contract feature fixes
echo.
pause
