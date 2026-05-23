@echo off
cd /d c:\laragon\www\stmarksms

echo ========================================
echo Running Laravel Setup Commands
echo ========================================

echo.
echo 1. Running migrations...
php artisan migrate

echo.
echo 2. Clearing cache...
php artisan cache:clear

echo.
echo 3. Clearing config cache...
php artisan config:clear

echo.
echo 4. Clearing view cache...
php artisan view:clear

echo.
echo 5. Clearing route cache...
php artisan route:clear

echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
pause
