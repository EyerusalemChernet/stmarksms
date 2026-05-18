# Employee Qualification Certificate Upload Feature

## Overview

This feature allows HR managers to upload and manage employee qualification certificates (diplomas, degrees, certifications, etc.) directly in the HR system. Certificates are stored securely and can be downloaded by authorized users.

---

## 🚀 Quick Start (3 Steps)

### Step 1: Run Setup
Open your browser and visit:
```
http://127.0.0.1:8000/quick-setup.php
```

Wait for the page to show "✅ Setup Complete!" - this will:
- Run database migrations
- Clear all caches
- Create storage directories
- Set up file storage links

### Step 2: Refresh Browser
Press **Ctrl+F5** (Windows) or **Cmd+Shift+R** (Mac) to clear browser cache.

### Step 3: Test the Feature
1. Go to **HR Module** → **Employees**
2. Click on any employee to view their profile
3. Click **"Edit Profile"** button
4. Scroll down to **"Qualifications"** section
5. Fill in qualification details and upload a certificate file
6. Click **"Save Changes"**
7. Go back to the employee profile
8. Scroll to **"Qualifications"** - you should see the certificate with a download link ✅

---

## 📋 How to Use

### Adding a Qualification with Certificate

1. **Navigate to Employee Profile**
   - Go to HR → Employees
   - Click on an employee name

2. **Click "Edit Profile"**
   - Scroll to the "Qualifications" section

3. **Fill in Qualification Details**
   - **Degree**: e.g., "BSc", "MSc", "PhD", "Diploma"
   - **Field of Study**: e.g., "Computer Science", "Business Administration"
   - **Institution**: e.g., "Addis Ababa University", "MIT"
   - **Graduation Year**: e.g., "2020"

4. **Upload Certificate**
   - Click "Choose file" button
   - Select a file (PDF, DOC, DOCX, JPG, PNG)
   - Maximum file size: 5MB

5. **Save Changes**
   - Click the "Save Changes" button at the bottom

6. **Verify Upload**
   - Go back to the employee profile
   - Scroll to "Qualifications"
   - You should see the certificate with a download link

### Viewing Certificates

In the employee profile (read-only view):
- Go to HR → Employees → Select Employee
- Scroll to "Qualifications" section
- Click the certificate link to download or view

### Editing Qualifications

1. Click "Edit Profile"
2. Modify the qualification details
3. To replace a certificate, upload a new file
4. Click "Save Changes"

### Removing Qualifications

**From Edit Profile:**
- Click the "Remove" button next to the qualification

**From Profile View:**
- Click the trash icon in the Qualifications table

---

## 📁 Supported File Formats

| Format | Extension | Max Size |
|--------|-----------|----------|
| PDF | .pdf | 5MB |
| Word Document | .doc, .docx | 5MB |
| Image (JPEG) | .jpg, .jpeg | 5MB |
| Image (PNG) | .png | 5MB |

---

## 🔧 Technical Details

### Database Changes
- Added `certificate_path` column to `employee_qualifications` table
- Stores relative path to the uploaded file

### File Storage
- **Location**: `storage/app/public/qualifications/{employee_id}/{filename}`
- **Access URL**: `/storage/qualifications/{employee_id}/{filename}`
- **Permissions**: Files are publicly accessible (can be downloaded)

### Model Methods
The `EmployeeQualification` model includes helper methods:

```php
// Get full URL to certificate
$url = $qualification->getCertificateUrl();

// Get just the filename
$filename = $qualification->getCertificateFileName();
```

### Controller Methods
The `HRController` includes:

```php
// updateProfile() - Handles file uploads when saving profile
// updateQualifications() - Private method that processes certificate files
```

---

## ❌ Troubleshooting

### Issue: "Setup Complete" but certificates still don't appear

**Solution:**
1. Clear browser cache: **Ctrl+Shift+Delete**
2. Close and reopen your browser
3. Try uploading again

### Issue: File upload fails with "File too large"

**Solution:**
- Maximum file size is 5MB
- Compress your PDF or image before uploading
- Try a different file format

### Issue: "Storage link not found" error

**Solution:**
Run this command in Command Prompt:
```bash
cd c:\laragon\www\stmarksms
php artisan storage:link
```

### Issue: Still not working after setup

**Solution:**
Run these commands in Command Prompt:
```bash
cd c:\laragon\www\stmarksms
php artisan migrate:status
```

Look for `2024_01_16_000000_add_certificate_to_employee_qualifications` - it should show "Ran"

If it shows "Pending", run:
```bash
php artisan migrate --force
```

Then clear all caches:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### Issue: Can't download certificates

**Possible causes:**
1. Storage link not created - run `php artisan storage:link`
2. File permissions - ensure `storage/` directory is writable
3. File was deleted - re-upload the certificate

---

## 📊 Database Schema

### employee_qualifications table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| employee_id | bigint | Foreign key to employees |
| degree | string | e.g., "BSc", "MSc" |
| field_of_study | string | nullable |
| institution | string | nullable |
| graduation_year | integer | nullable |
| certificate_path | string | **NEW** - Path to uploaded file |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 🔐 Security Notes

- Files are stored outside the web root (`storage/` directory)
- Access is controlled through Laravel's storage system
- Only authenticated HR managers can upload/download
- File types are validated (PDF, DOC, DOCX, JPG, PNG only)
- File size is limited to 5MB

---

## 📝 Files Modified/Created

### New Files
- `database/migrations/2024_01_16_000000_add_certificate_to_employee_qualifications.php`
- `quick-setup.php` (improved setup script)
- `QUALIFICATION_UPLOAD_GUIDE.md` (this file)

### Modified Files
- `app/Models/EmployeeQualification.php` - Added helper methods
- `app/Http/Controllers/SupportTeam/HRController.php` - Added file upload handling
- `resources/views/pages/hr/profile_edit.blade.php` - Added file upload form
- `resources/views/pages/hr/show.blade.php` - Added certificate display

---

## ✅ Verification Checklist

After setup, verify:

- [ ] Run `quick-setup.php` in browser
- [ ] Refresh browser (Ctrl+F5)
- [ ] Go to HR → Employees
- [ ] Select an employee
- [ ] Click "Edit Profile"
- [ ] Scroll to "Qualifications"
- [ ] Upload a test certificate
- [ ] Click "Save Changes"
- [ ] Go back to employee profile
- [ ] Verify certificate appears with download link
- [ ] Click download link to verify file works

---

## 🆘 Need Help?

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Run the setup script again: `http://127.0.0.1:8000/quick-setup.php`
3. Clear all caches and try again
4. Check file permissions on `storage/` directory

---

## 📞 Support

If you encounter issues:
1. Check this guide's Troubleshooting section
2. Review the Laravel logs
3. Ensure all setup steps were completed
4. Try the setup script again

