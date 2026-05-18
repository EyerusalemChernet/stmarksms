# ✅ Employee Qualification Certificate Upload - Setup Checklist

## 📋 Pre-Setup Checklist

- [ ] Laravel server is running (`php artisan serve`)
- [ ] Browser is open and ready
- [ ] You have admin/HR manager access to the system
- [ ] You have at least one employee in the system

---

## 🚀 Setup Steps

### Step 1: Run Setup Script

**Choose ONE of these options:**

#### Option A: Browser Setup (Recommended) ⭐
- [ ] Open browser
- [ ] Go to: `http://127.0.0.1:8000/quick-setup.php`
- [ ] Wait for page to load completely
- [ ] See "✅ Setup Complete!" message
- [ ] Note any warnings or errors

#### Option B: Command Prompt
- [ ] Open Command Prompt
- [ ] Navigate to: `c:\laragon\www\stmarksms`
- [ ] Run: `setup-qualifications.bat`
- [ ] Wait for completion
- [ ] See "✓ Setup Complete!" message

#### Option C: PowerShell
- [ ] Open PowerShell
- [ ] Navigate to: `c:\laragon\www\stmarksms`
- [ ] Run: `powershell -ExecutionPolicy Bypass -File setup-qualifications.ps1`
- [ ] Wait for completion
- [ ] See "✓ Setup Complete!" message

#### Option D: Manual Commands
- [ ] Open Command Prompt
- [ ] Navigate to: `c:\laragon\www\stmarksms`
- [ ] Run: `php artisan migrate --force`
- [ ] Run: `php artisan cache:clear`
- [ ] Run: `php artisan config:clear`
- [ ] Run: `php artisan view:clear`
- [ ] Run: `php artisan route:clear`
- [ ] Run: `php artisan storage:link`

---

### Step 2: Refresh Browser

- [ ] Press **Ctrl+F5** (Windows) or **Cmd+Shift+R** (Mac)
- [ ] Wait for page to reload
- [ ] Verify page loads without errors

---

### Step 3: Verify Setup

- [ ] Go to: **HR → Employees**
- [ ] Click on any employee name
- [ ] Click **"Edit Profile"** button
- [ ] Scroll down to **"Qualifications"** section
- [ ] Verify you see the qualifications form with file upload input
- [ ] Verify "Add Qualification" button is visible

---

## 🧪 Testing Steps

### Test 1: Upload a Certificate

- [ ] In Edit Profile, scroll to "Qualifications"
- [ ] Fill in qualification details:
  - [ ] Degree: "BSc" (or any degree)
  - [ ] Field of Study: "Computer Science" (or any field)
  - [ ] Institution: "Test University" (or any institution)
  - [ ] Graduation Year: "2020" (or any year)
- [ ] Click "Choose file" button
- [ ] Select a test file (PDF, DOC, DOCX, JPG, or PNG)
- [ ] Verify file name appears in the input
- [ ] Click "Save Changes" button
- [ ] Wait for page to redirect
- [ ] Verify success message appears

### Test 2: View Certificate

- [ ] Go back to employee profile (click "Back to Profile" or navigate)
- [ ] Scroll to "Qualifications" section
- [ ] Verify you see the qualification in the table
- [ ] Verify the certificate appears with a download link
- [ ] Verify the link shows the file name

### Test 3: Download Certificate

- [ ] Click the certificate download link
- [ ] Verify file downloads to your computer
- [ ] Verify file is not corrupted (can open it)

### Test 4: Edit Qualification

- [ ] Click "Edit Profile" again
- [ ] Scroll to "Qualifications"
- [ ] Modify the qualification details (e.g., change degree to "MSc")
- [ ] Upload a different certificate file
- [ ] Click "Save Changes"
- [ ] Go back to profile
- [ ] Verify changes appear

### Test 5: Delete Qualification

- [ ] Click "Edit Profile"
- [ ] Scroll to "Qualifications"
- [ ] Click "Remove" button next to the qualification
- [ ] Click "Save Changes"
- [ ] Go back to profile
- [ ] Verify qualification is gone

---

## ✅ Post-Setup Verification

### Database
- [ ] Migration has been run
  - Run: `php artisan migrate:status`
  - Look for: `2024_01_16_000000_add_certificate_to_employee_qualifications` showing "Ran"

### Storage
- [ ] Storage link exists
  - Check: `public/storage` folder exists
  - Check: It's a link to `storage/app/public`

### Directories
- [ ] Qualifications directory exists
  - Check: `storage/app/public/qualifications/` exists
  - Check: Directory is writable

### Files
- [ ] Uploaded files are stored correctly
  - Check: `storage/app/public/qualifications/{employee_id}/` contains uploaded files

### Caches
- [ ] All caches have been cleared
  - Check: `storage/bootstrap/cache/` is mostly empty
  - Check: No old view cache files

---

## 🆘 Troubleshooting Checklist

### If Feature Not Showing

- [ ] Clear browser cache: **Ctrl+Shift+Delete**
- [ ] Refresh browser: **Ctrl+F5**
- [ ] Run setup again: `http://127.0.0.1:8000/quick-setup.php`
- [ ] Check Laravel logs: `type storage\logs\laravel.log`
- [ ] Restart Laravel server: Stop (Ctrl+C) and run `php artisan serve` again

### If File Upload Fails

- [ ] Check file size (must be less than 5MB)
- [ ] Check file format (must be PDF, DOC, DOCX, JPG, or PNG)
- [ ] Try a different file
- [ ] Check storage directory permissions
- [ ] Check Laravel logs for errors

### If Downloaded File Corrupted

- [ ] Re-upload the file
- [ ] Try a different file format
- [ ] Check file size (max 5MB)
- [ ] Check storage directory permissions

### If Still Not Working

- [ ] Run setup again
- [ ] Check Laravel logs: `type storage\logs\laravel.log`
- [ ] Verify database migration: `php artisan migrate:status`
- [ ] Verify storage link: Check `public/storage` exists
- [ ] Check file permissions: `icacls storage /grant:r "%username%":F /t`

---

## 📞 Support Resources

### Documentation Files
- [ ] Read: `SETUP_README.md` - Quick start guide
- [ ] Read: `QUALIFICATION_UPLOAD_GUIDE.md` - Complete user guide
- [ ] Read: `CERTIFICATE_UPLOAD_FIX.md` - Technical details
- [ ] Read: `QUICK_REFERENCE.txt` - Quick reference card

### Setup Scripts
- [ ] `quick-setup.php` - Browser setup
- [ ] `setup-qualifications.bat` - Command Prompt setup
- [ ] `setup-qualifications.ps1` - PowerShell setup

### Logs
- [ ] Check: `storage/logs/laravel.log`

---

## 🎯 Final Checklist

- [ ] Setup script has been run successfully
- [ ] Browser has been refreshed (Ctrl+F5)
- [ ] Can see qualifications form in edit profile
- [ ] Can upload a certificate file
- [ ] Can see certificate in employee profile
- [ ] Can download certificate file
- [ ] Can edit qualifications
- [ ] Can delete qualifications
- [ ] All tests passed
- [ ] No errors in Laravel logs

---

## ✨ Success Criteria

You'll know the feature is working when:

✅ You can upload a certificate file
✅ The file appears in the employee profile
✅ You can download the file
✅ The downloaded file is not corrupted
✅ You can edit and delete qualifications
✅ No errors appear in the system

---

## 📝 Notes

- **Setup Time:** 2-5 minutes
- **Testing Time:** 5-10 minutes
- **Total Time:** 10-15 minutes

---

## 🎉 Completion

Once all checkboxes are checked, the feature is ready for production use!

**Date Completed:** _______________

**Tested By:** _______________

**Notes:** _______________________________________________

---

**Status:** ✅ Ready to Use

