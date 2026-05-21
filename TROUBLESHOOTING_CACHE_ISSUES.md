# 🔧 Troubleshooting: Changes Not Visible After Cache Clear

## Problem
You've cleared the cache and pressed Ctrl+F5, but you're still seeing the old version of the website.

## Root Causes

This usually happens because of one of these reasons:

1. **Laravel development server is still running the old code** (most common)
2. **Browser cache is still holding old files**
3. **Compiled view files weren't fully deleted**
4. **PHP opcache is caching the old code**

## Solution (Step by Step)

### Step 1: Stop the Laravel Development Server

If you have a Laravel development server running (from `php artisan serve`), you MUST stop it and restart it.

**In PowerShell:**
```powershell
# Press Ctrl+C to stop the server
```

**In Command Prompt:**
```cmd
# Press Ctrl+C to stop the server
```

### Step 2: Run Aggressive Cache Clear

Run one of these scripts to clear ALL caches:

**Option A: Using Batch File (Recommended for Windows)**
```powershell
.\AGGRESSIVE_CACHE_CLEAR.bat
```

**Option B: Using PowerShell**
```powershell
.\AGGRESSIVE_CACHE_CLEAR.ps1
```

**Option C: Manual Commands**
```powershell
$PHP = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
& $PHP artisan cache:clear
& $PHP artisan config:clear
& $PHP artisan view:clear
& $PHP artisan route:clear
& $PHP artisan optimize:clear
Remove-Item "bootstrap\cache\*" -Force -Recurse
Remove-Item "storage\framework\cache\*" -Force -Recurse
Remove-Item "storage\framework\views\*" -Force -Recurse
```

### Step 3: Restart Laravel Development Server

After clearing caches, restart the development server:

```powershell
$PHP = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
& $PHP artisan serve
```

Wait for it to say "Server running at http://127.0.0.1:8000"

### Step 4: Clear Browser Cache

Now clear your browser cache:

**Chrome/Edge:**
- Press `Ctrl+Shift+Delete`
- Select "All time"
- Check "Cookies and other site data" and "Cached images and files"
- Click "Clear data"

**Firefox:**
- Press `Ctrl+Shift+Delete`
- Select "Everything"
- Click "Clear Now"

### Step 5: Hard Refresh Browser

Press `Ctrl+F5` (or `Cmd+Shift+R` on Mac) to do a hard refresh.

### Step 6: Test the Changes

Navigate to **HR → Contract Management** and verify:
- ✅ Confirmation dialog appears when clicking "Renew Contract"
- ✅ Readable date format shows in modal
- ✅ Max date validation works (try setting date 10+ years in future)

## If Still Not Working

### Check 1: Verify Files Have Changes

Open these files in your editor and verify they contain the changes:

**File 1: `resources/views/pages/hr/contracts.blade.php`**
- Search for: `confirmRenewal`
- Should find: `function confirmRenewal(event) {`

**File 2: `resources/views/pages/hr/profile_edit.blade.php`**
- Search for: `qualifications[{{ $i }}][id]`
- Should find: `<input type="hidden" name="qualifications[{{ $i }}][id]"`

**File 3: `app/Http/Controllers/SupportTeam/HRController.php`**
- Search for: `addYears(10)`
- Should find: `$maxDate = now()->addYears(10)->format('Y-m-d');`

If these files DON'T have the changes, the code wasn't properly committed. Contact support.

### Check 2: Verify Git Status

Check if changes are actually committed:

```powershell
git status
git log --oneline -5
```

You should see a commit with message like "Fix contract feature issues..."

### Check 3: Check Laravel Logs

Look for errors in the Laravel log:

```powershell
Get-Content storage/logs/laravel.log -Tail 50
```

Look for any PHP errors or exceptions.

### Check 4: Try Different Browser

Try a completely different browser (Chrome, Firefox, Edge) to rule out browser-specific cache issues.

### Check 5: Clear PHP Opcache

If you're using PHP opcache, it might be caching the old code:

```powershell
$PHP = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
& $PHP -r "opcache_reset();"
```

### Check 6: Disable Browser Extensions

Some browser extensions (like caching extensions) can interfere. Try:
1. Open browser in Incognito/Private mode
2. Navigate to http://127.0.0.1:8000
3. Test the changes

If it works in Incognito mode, a browser extension is the problem.

## Nuclear Option: Complete Reset

If nothing else works, do a complete reset:

```powershell
# 1. Stop Laravel server (Ctrl+C)

# 2. Delete all cache directories
Remove-Item "bootstrap\cache\*" -Force -Recurse
Remove-Item "storage\framework\cache\*" -Force -Recurse
Remove-Item "storage\framework\views\*" -Force -Recurse
Remove-Item "storage\logs\*" -Force -Recurse

# 3. Recreate directories
New-Item -ItemType Directory -Path "bootstrap\cache" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\framework\cache" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\framework\views" -Force | Out-Null

# 4. Clear browser cache (Ctrl+Shift+Delete)

# 5. Restart Laravel server
$PHP = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
& $PHP artisan serve

# 6. Hard refresh browser (Ctrl+F5)
```

## Verification Checklist

After following these steps, verify:

- [ ] Laravel development server is running
- [ ] All cache directories are empty
- [ ] Browser cache is cleared
- [ ] Hard refresh done (Ctrl+F5)
- [ ] Tried in Incognito/Private mode
- [ ] Tried in different browser
- [ ] Files contain the expected changes
- [ ] Git shows the changes are committed

## Still Having Issues?

If you've followed all these steps and still see the old version:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Check browser console for errors: F12 → Console tab
3. Verify the files actually have the changes (read them in editor)
4. Check git history: `git log --oneline -10`

---

**Last Resort:** If absolutely nothing works, you may need to:
1. Clone the repository fresh in a new directory
2. Run `composer install`
3. Copy your `.env` file
4. Run `php artisan migrate`
5. Test in the new directory

This will rule out any local cache/compilation issues.
