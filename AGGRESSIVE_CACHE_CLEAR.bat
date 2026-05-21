@echo off
REM Aggressive Cache Clear Script for Laravel
REM This script clears ALL caches and compiled files

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║                                                                ║
echo ║           AGGRESSIVE CACHE CLEAR - LARAVEL                    ║
echo ║                                                                ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

setlocal enabledelayedexpansion

set PHP_PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe

echo [1/8] Clearing application cache...
"%PHP_PATH%" artisan cache:clear
if errorlevel 1 echo ERROR: Failed to clear application cache

echo [2/8] Clearing config cache...
"%PHP_PATH%" artisan config:clear
if errorlevel 1 echo ERROR: Failed to clear config cache

echo [3/8] Clearing view cache...
"%PHP_PATH%" artisan view:clear
if errorlevel 1 echo ERROR: Failed to clear view cache

echo [4/8] Clearing route cache...
"%PHP_PATH%" artisan route:clear
if errorlevel 1 echo ERROR: Failed to clear route cache

echo [5/8] Clearing compiled classes...
"%PHP_PATH%" artisan optimize:clear
if errorlevel 1 echo ERROR: Failed to clear compiled classes

echo [6/8] Deleting bootstrap cache files...
if exist "bootstrap\cache\*.php" (
    del /q "bootstrap\cache\*.php"
    echo     ✓ Bootstrap cache cleared
) else (
    echo     ✓ Bootstrap cache already empty
)

echo [7/8] Deleting storage framework cache...
if exist "storage\framework\cache\*" (
    rmdir /s /q "storage\framework\cache" 2>nul
    mkdir "storage\framework\cache"
    echo     ✓ Storage cache cleared
) else (
    echo     ✓ Storage cache already empty
)

echo [8/8] Deleting storage framework views...
if exist "storage\framework\views\*" (
    rmdir /s /q "storage\framework\views" 2>nul
    mkdir "storage\framework\views"
    echo     ✓ Storage views cleared
) else (
    echo     ✓ Storage views already empty
)

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║                                                                ║
echo ║              ✅ ALL CACHES CLEARED SUCCESSFULLY               ║
echo ║                                                                ║
echo ║  NEXT STEPS:                                                   ║
echo ║  1. Restart your Laravel development server                   ║
echo ║  2. Press Ctrl+F5 in your browser                             ║
echo ║  3. Navigate to HR → Contract Management                      ║
echo ║                                                                ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

pause
