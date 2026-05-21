# Fix: PHP Not Found Error

## Problem

When you try to run `php artisan` commands in PowerShell, you get:
```
php : The term 'php' is not recognized as the name of a cmdlet, function, script file, or operable program.
```

This happens because PHP is not in your system PATH, even though it's installed in Laragon.

---

## Solution

### Option 1: Use the Updated Batch File (Easiest) ⭐ RECOMMENDED

I've updated the batch file to use the full path to PHP. Just run:

```bash
clear-caches.bat
```

**Steps:**
1. Open Command Prompt (not PowerShell)
2. Navigate to: `c:\laragon\www\stmarksms`
3. Run: `clear-caches.bat`
4. Wait for completion
5. Refresh browser (Ctrl+F5)

---

### Option 2: Use the PowerShell Script

I've created a PowerShell script that uses the full path to PHP:

```bash
powershell -ExecutionPolicy Bypass -File clear-caches.ps1
```

**Steps:**
1. Open PowerShell
2. Navigate to: `c:\laragon\www\stmarksms`
3. Run: `powershell -ExecutionPolicy Bypass -File clear-caches.ps1`
4. Wait for completion
5. Refresh browser (Ctrl+F5)

---

### Option 3: Use Full Path to PHP Manually

If you want to run commands manually, use the full path:

```bash
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan cache:clear
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan config:clear
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan view:clear
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan route:clear
```

---

### Option 4: Add PHP to System PATH (Permanent Fix)

To permanently fix this, add PHP to your system PATH:

1. Open **Environment Variables**:
   - Press `Win + X` and select "System"
   - Click "Advanced system settings"
   - Click "Environment Variables"

2. Under "System variables", click "New"
   - Variable name: `PATH`
   - Variable value: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64`

3. Click OK and restart PowerShell

4. Now you can use `php artisan` commands directly

---

## Quick Steps to Clear Cache

### Using Batch File (Recommended)

1. Open Command Prompt
2. Navigate to: `c:\laragon\www\stmarksms`
3. Run: `clear-caches.bat`
4. Press Enter when done
5. Refresh browser (Ctrl+F5)

### Using PowerShell Script

1. Open PowerShell
2. Navigate to: `c:\laragon\www\stmarksms`
3. Run: `powershell -ExecutionPolicy Bypass -File clear-caches.ps1`
4. Press Enter when done
5. Refresh browser (Ctrl+F5)

### Using Browser Setup (Easiest)

1. Open browser: `http://127.0.0.1:8000/quick-setup.php`
2. Wait for "✅ Setup Complete!"
3. Refresh browser (Ctrl+F5)

---

## Verify the Fixes

After clearing cache:

1. Go to **HR → Contract Management**
2. Click **"Renew Contract"** button
3. You should see:
   - ✅ Confirmation dialog
   - ✅ Readable date format
   - ✅ Both ISO and readable formats

---

## Files Created

- **`clear-caches.bat`** - Updated batch file with full PHP path
- **`clear-caches.ps1`** - PowerShell script with full PHP path
- **`FIX_PHP_NOT_FOUND.md`** - This file

---

## Troubleshooting

### Still getting "php not found" error?

1. **Check PHP path:**
   - Open File Explorer
   - Navigate to: `C:\laragon\bin\php\`
   - You should see a folder like `php-8.3.30-Win32-vs16-x64`
   - If not, check your Laragon installation

2. **Use Command Prompt instead of PowerShell:**
   - Command Prompt is more reliable for running batch files
   - Open Command Prompt (not PowerShell)
   - Run: `clear-caches.bat`

3. **Use the browser setup:**
   - Go to: `http://127.0.0.1:8000/quick-setup.php`
   - This doesn't require PHP in PATH

---

## Summary

The issue is that PHP is not in your system PATH. Use one of these solutions:

1. **Easiest:** Run `clear-caches.bat` (uses full PHP path)
2. **Alternative:** Run `clear-caches.ps1` (PowerShell script)
3. **Browser:** Go to `http://127.0.0.1:8000/quick-setup.php`
4. **Permanent:** Add PHP to system PATH

After clearing cache, refresh your browser (Ctrl+F5) and the contract feature fixes will be visible!

