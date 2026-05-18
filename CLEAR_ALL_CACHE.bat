@echo off
REM This script clears ALL Laravel caches to make changes visible
REM It uses the full path to PHP from Laragon

setlocal enabledelayedexpansion

echo.
echo ========================================
echo CLEARING ALL LARAVEL CACHES
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
echo Project: %PROJECT_PATH%
echo.

REM Change to project directory
cd /d "%PROJECT_PATH%"

REM Step 1: Clear application cache
echo [1/6] Clearing application cache...
"%PHP_PATH%" artisan cache:clear
if errorlevel 1 (
    echo ERROR: Failed to clear cache
    pause
    exit /b 1
)
echo ✓ Done
echo.

REM Step 2: Clear config cache
echo [2/6] Clearing config cache...
"%PHP_PATH%" artisan config:clear
if errorlevel 1 (
    echo ERROR: Failed to clear config
    pause
    exit /b 1
)
echo ✓ Done
echo.

REM Step 3: Clear view cache
echo [3/6] Clearing view cache...
"%PHP_PATH%" artisan view:clear
if errorlevel 1 (
    echo ERROR: Failed to clear views
    pause
    exit /b 1
)
echo ✓ Done
echo.

REM Step 4: Clear route cache
echo [4/6] Clearing route cache...
"%PHP_PATH%" artisan route:clear
if errorlevel 1 (
    echo ERROR: Failed to clear routes
    pause
    exit /b 1
)
echo ✓ Done
echo.

REM Step 5: Clear compiled classes
echo [5/6] Clearing compiled classes...
"%PHP_PATH%" artisan clear-compiled
if errorlevel 1 (
    echo WARNING: Could not clear compiled classes (this is OK)
)
echo ✓ Done
echo.

REM Step 6: Optimize autoloader
echo [6/6] Optimizing autoloader...
"%PHP_PATH%" composer dump-autoload
if errorlevel 1 (
    echo WARNING: Could not optimize autoloader (this is OK)
)
echo ✓ Done
echo.

echo ========================================
echo ✓ ALL CACHES CLEARED SUCCESSFULLY!
echo ========================================
echo.
echo IMPORTANT: Now do this:
echo.
echo 1. Refresh your browser (Ctrl+F5)
echo 2. Go to HR ^> Contract Management
echo 3. You should now see the contract feature fixes
echo.
echo If you still don't see changes:
echo - Try a different browser
echo - Restart your Laravel server
echo - Check the browser console for errors (F12)
echo.
pause
