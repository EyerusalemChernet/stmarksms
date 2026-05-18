# Setup Instructions for Qualification File Upload Feature

## Problem
The qualification file upload feature is not working because:
1. The database migration hasn't been run yet
2. Laravel cache needs to be cleared

## Solution

### Option 1: Run the Batch File (Easiest)
1. Double-click `run-setup.bat` in the project root
2. Wait for all commands to complete
3. Close the window

### Option 2: Run Commands Manually in Terminal

Open Command Prompt or PowerShell in the project directory and run:

```bash
# 1. Run migrations to add certificate_path column
php artisan migrate

# 2. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### Option 3: Using Laragon

1. Open Laragon
2. Right-click on the stmarksms project
3. Click "Terminal"
4. Run the commands above

## After Setup

1. **Refresh your browser** (Ctrl+F5 or Cmd+Shift+R)
2. **Go to HR → Edit Employee Profile**
3. **Scroll to Qualifications section**
4. **Upload a certificate file**
5. **Click "Save Changes"**
6. **Go to Employee Profile (Show page)**
7. **Scroll to Qualifications**
8. **You should now see the certificate with a download link** ✅

## What Changed

### Database
- Added `certificate_path` column to `employee_qualifications` table

### Controller (HRController.php)
- Added file upload handling in `updateProfile()` method
- Created `updateQualifications()` method to process files
- Files are stored in `storage/qualifications/{employee_id}/`

### Views
- `profile_edit.blade.php`: Added file upload input for each qualification
- `show.blade.php`: Added certificate column to display uploaded files

## File Upload Details

**Supported Formats:**
- PDF (.pdf)
- Word Documents (.doc, .docx)
- Images (.jpg, .jpeg, .png)

**Max File Size:** 5MB

**Storage Location:** `storage/qualifications/{employee_id}/{filename}`

**Access URL:** `/storage/qualifications/{employee_id}/{filename}`

## Troubleshooting

### Still not working?

1. **Check if migration ran:**
   ```bash
   php artisan migrate:status
   ```
   Look for `2024_01_16_000000_add_certificate_to_employee_qualifications` - should show "Ran"

2. **Check storage link:**
   ```bash
   php artisan storage:link
   ```

3. **Check file permissions:**
   - Make sure `storage/` directory is writable
   - Make sure `public/storage/` exists

4. **Clear everything:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   composer dump-autoload
   ```

5. **Restart your server:**
   - Stop the Laravel server (Ctrl+C)
   - Run `php artisan serve` again

## Questions?

If you still have issues:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Make sure you're using the latest code from the repository
3. Verify the migration file exists: `database/migrations/2024_01_16_000000_add_certificate_to_employee_qualifications.php`
