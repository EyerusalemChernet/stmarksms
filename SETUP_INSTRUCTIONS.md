# Setup Instructions for Qualification File Upload Feature

## Quick Setup (Easiest - 3 Steps)

### Step 1: Run the Setup Script
Open your browser and go to:
```
http://127.0.0.1:8000/quick-setup.php
```

Wait for the page to complete. You should see:
```
✓ Setup complete!
```

### Step 2: Refresh Your Browser
Press **Ctrl+F5** (Windows) or **Cmd+Shift+R** (Mac)

### Step 3: Test the Feature
1. Go to **HR → Select an Employee**
2. Click **"Edit Profile"**
3. Scroll to **"Qualifications"** section
4. **Upload a certificate file** (PDF, DOC, DOCX, JPG, PNG)
5. Click **"Save Changes"**
6. Go back to **Employee Profile**
7. Scroll to **"Qualifications"**
8. **You should see the certificate with a download link** ✅

---

## Alternative: Manual Setup

If the quick setup doesn't work, run these commands in Command Prompt:

```bash
cd c:\laragon\www\stmarksms

php artisan migrate --force
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan storage:link
```

---

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

6. **Check the logs:**
   ```bash
   type storage\logs\laravel.log
   ```

## Questions?

If you still have issues:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Make sure you're using the latest code from the repository
3. Verify the migration file exists: `database/migrations/2024_01_16_000000_add_certificate_to_employee_qualifications.php`

