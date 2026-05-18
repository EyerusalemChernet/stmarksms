# Fix: Certificate Uploads But Not Showing in Profile

## Problem
- ✅ Certificate file uploads successfully
- ❌ Certificate does NOT appear in employee profile with download button

## Root Causes

This can happen for several reasons:

1. **Hidden ID field missing** - The qualification ID wasn't being sent to the controller
2. **Certificate path not saved** - The file path wasn't being stored in the database
3. **Storage link not created** - Files can't be accessed via HTTP
4. **Database migration not run** - The `certificate_path` column doesn't exist
5. **Cache not cleared** - Old views are being cached

## Solution

### Step 1: Apply Code Fix

The hidden ID field was missing from the form. This has been fixed in:
- **File:** `resources/views/pages/hr/profile_edit.blade.php`
- **Change:** Added hidden input for qualification ID

The controller was also improved to preserve existing certificate paths:
- **File:** `app/Http/Controllers/SupportTeam/HRController.php`
- **Change:** Updated `updateQualifications()` method

### Step 2: Clear Caches

Run these commands:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### Step 3: Refresh Browser

Press **Ctrl+F5** (Windows) or **Cmd+Shift+R** (Mac)

### Step 4: Test Again

1. Go to HR → Employees
2. Select an employee
3. Click "Edit Profile"
4. Scroll to "Qualifications"
5. Upload a certificate
6. Click "Save Changes"
7. Go back to profile
8. **Certificate should now appear with download button** ✅

---

## Diagnostic Steps

If it still doesn't work, run the diagnostic script:

```
http://127.0.0.1:8000/diagnose-certificates.php
```

This will check:
- ✅ Database migration status
- ✅ Storage directory exists and is writable
- ✅ Storage link is created
- ✅ Model methods exist
- ✅ View files are correct
- ✅ Controller methods exist
- ✅ Recent errors in logs

---

## Common Issues & Fixes

### Issue 1: "Storage link not found" error

**Fix:**
```bash
php artisan storage:link
```

### Issue 2: "Directory not writable" error

**Fix:**
```bash
icacls storage /grant:r "%username%":F /t
```

### Issue 3: Migration shows as "Pending"

**Fix:**
```bash
php artisan migrate --force
```

### Issue 4: Still not showing after all fixes

**Check:**
1. Run diagnostic: `http://127.0.0.1:8000/diagnose-certificates.php`
2. Check logs: `type storage\logs\laravel.log`
3. Verify database: `php artisan tinker` then `DB::table('employee_qualifications')->first()`

---

## What Was Fixed

### 1. Hidden ID Field Added
**File:** `resources/views/pages/hr/profile_edit.blade.php`

**Before:**
```php
@foreach($qualifications as $i => $qual)
<div class="qualification-row border rounded p-3 mb-3">
    <div class="form-row">
        <!-- form fields -->
```

**After:**
```php
@foreach($qualifications as $i => $qual)
<div class="qualification-row border rounded p-3 mb-3">
    @if(!empty($qual['id']))
    <input type="hidden" name="qualifications[{{ $i }}][id]" value="{{ $qual['id'] }}">
    @endif
    <div class="form-row">
        <!-- form fields -->
```

### 2. Controller Updated
**File:** `app/Http/Controllers/SupportTeam/HRController.php`

**Improvement:** The `updateQualifications()` method now:
- Preserves existing certificate paths when updating
- Only updates the certificate_path if a new file is uploaded
- Properly handles both create and update operations

---

## Testing Checklist

After applying the fix:

- [ ] Clear caches (cache:clear, config:clear, view:clear, route:clear)
- [ ] Refresh browser (Ctrl+F5)
- [ ] Go to HR → Employees
- [ ] Select an employee
- [ ] Click "Edit Profile"
- [ ] Scroll to "Qualifications"
- [ ] Fill in qualification details
- [ ] Upload a certificate file
- [ ] Click "Save Changes"
- [ ] Go back to employee profile
- [ ] Verify certificate appears in Qualifications table
- [ ] Verify download link is visible
- [ ] Click download link to verify file works

---

## Verification

To verify the fix is working:

1. **Check database:**
   ```bash
   php artisan tinker
   >>> $q = DB::table('employee_qualifications')->first();
   >>> $q->certificate_path
   ```
   Should show a path like: `qualifications/1/filename.pdf`

2. **Check file exists:**
   ```bash
   dir storage\app\public\qualifications\
   ```
   Should show employee ID folders with uploaded files

3. **Check storage link:**
   ```bash
   dir public\storage
   ```
   Should show `qualifications` folder

---

## Files Modified

1. **`resources/views/pages/hr/profile_edit.blade.php`**
   - Added hidden ID field for qualifications

2. **`app/Http/Controllers/SupportTeam/HRController.php`**
   - Improved `updateQualifications()` method
   - Better handling of certificate paths

---

## Next Steps

1. Apply the code fix (already done)
2. Clear caches
3. Refresh browser
4. Test the feature
5. If still not working, run diagnostic script

---

## Support

If you still have issues:

1. **Run diagnostic:** `http://127.0.0.1:8000/diagnose-certificates.php`
2. **Check logs:** `type storage\logs\laravel.log`
3. **Run setup again:** `http://127.0.0.1:8000/quick-setup.php`
4. **Check database:** `php artisan tinker` then query the database

---

**Status:** ✅ Fixed

**Last Updated:** May 18, 2026

