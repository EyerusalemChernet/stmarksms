# Recruitment Module - Testing Guide

## Quick Start Testing

### Step 1: Clear Cache
```bash
php artisan cache:clear
php artisan view:clear
```

Then press **Ctrl+F5** in browser to clear browser cache.

### Step 2: Test Application Submission with Resume

1. Go to: `http://127.0.0.1:8000/hr/hr/recruitment/applications/create`
2. Select a job posting (must have status = "open")
3. Fill in applicant details:
   - First Name: John
   - Last Name: Doe
   - Email: john@example.com
   - Phone: 0912345678
   - Address: Addis Ababa
4. **Attach a resume file** (PDF, DOC, or DOCX, max 5MB)
5. Optionally add cover letter
6. Click "Submit Application"

**Expected Result:**
- Application created successfully
- Resume file uploaded to `storage/app/public/applications/{job_posting_id}/{timestamp}_{filename}`
- Redirected to application details page
- Success message: "Application submitted successfully. Resume uploaded."

### Step 3: View Application and Resume

1. Go to: `http://127.0.0.1:8000/hr/hr/recruitment/applications`
2. Click on the application you just created
3. Scroll down to see the resume section

**Expected Result:**
- Application details displayed
- Resume section visible with:
  - Resume filename
  - "Download Resume" button
- Pipeline visualization showing "Applied" status

### Step 4: Download Resume

1. In application details page, click "Download Resume" button
2. File should download to your computer

**Expected Result:**
- Resume file downloads successfully
- File name matches the uploaded file

### Step 5: Update Application Status

1. In application details page, scroll to "Update Status" section
2. Change status to "Shortlisted"
3. Optionally set interview date
4. Add a note: "Good candidate"
5. Click "Update"

**Expected Result:**
- Status updated to "Shortlisted"
- Note added to history
- Pipeline visualization updated
- Audit log created

### Step 6: Test Authorization

1. Log out and log in as a non-HR user
2. Try to access: `http://127.0.0.1:8000/hr/hr/recruitment/applications`

**Expected Result:**
- Access denied (403 error) or redirected to dashboard
- Non-HR users cannot access recruitment module

## Detailed Test Cases

### Test Case 1: Resume Upload Validation

**Scenario:** Upload invalid file type

1. Go to application create page
2. Try to upload a .txt file
3. Click Submit

**Expected Result:**
- Form validation error: "The resume field must be a file of type: pdf, doc, docx."
- Application not created

**Scenario:** Upload file larger than 5MB

1. Go to application create page
2. Try to upload a file > 5MB
3. Click Submit

**Expected Result:**
- Form validation error: "The resume field must not be greater than 5120 kilobytes."
- Application not created

### Test Case 2: Resume Display

**Scenario:** Application without resume

1. Create application without attaching resume
2. View application details

**Expected Result:**
- Resume section not displayed
- No download button shown

**Scenario:** Application with resume

1. Create application with resume attached
2. View application details

**Expected Result:**
- Resume section displayed with green border
- Filename shown
- Download button available

### Test Case 3: Resume Download Authorization

**Scenario:** HR user downloads resume

1. Log in as HR user
2. Go to application details
3. Click "Download Resume"

**Expected Result:**
- Resume downloads successfully

**Scenario:** Non-HR user tries to download resume

1. Log in as non-HR user
2. Try to access: `/hr/recruitment/applications/{id}/download-resume`

**Expected Result:**
- 403 Forbidden error
- Resume not downloaded

### Test Case 4: Application Pipeline

**Scenario:** Track application through pipeline

1. Create application (status: Applied)
2. Update to Shortlisted
3. Update to Interviewed
4. Update to Hired

**Expected Result:**
- Each status change creates a note in history
- Pipeline visualization updates
- Audit log created for each change

### Test Case 5: Convert to Employee

**Scenario:** Convert hired applicant to employee

1. Create application
2. Update status to "Hired"
3. Click "Convert to Employee" button

**Expected Result:**
- Redirected to employee creation form
- Form pre-filled with applicant data:
  - First Name
  - Last Name
  - Email
  - Phone
  - Address
  - Department (from job posting)
  - Position (from job posting)
  - Employment Type (from job posting)
  - Hire Date (today)

### Test Case 6: Search and Filter

**Scenario:** Search applications

1. Go to applications list
2. Enter search term: "John"
3. Click Search

**Expected Result:**
- Applications filtered by name, email, phone, or job posting title
- Only matching applications displayed

**Scenario:** Filter by status

1. Go to applications list
2. Click "Shortlisted" tab

**Expected Result:**
- Only shortlisted applications displayed
- Tab highlighted
- Count shows correct number

**Scenario:** Filter by job posting

1. Go to applications list
2. Select job posting from dropdown
3. Click Search

**Expected Result:**
- Only applications for selected posting displayed

### Test Case 7: Export Functionality

**Scenario:** Export applications to PDF

1. Go to applications list
2. Click "PDF" button

**Expected Result:**
- PDF file downloads
- Contains all application data

**Scenario:** Export applications to CSV

1. Go to applications list
2. Click "CSV" button

**Expected Result:**
- CSV file downloads
- Can be opened in Excel/Sheets

## Database Verification

### Check Resume File Storage

```bash
# List uploaded resumes
dir storage\app\public\applications\

# Check file permissions
icacls storage\app\public\applications\
```

### Check Database Records

```sql
-- View applications with resumes
SELECT id, first_name, last_name, resume_path, status 
FROM job_applications 
WHERE resume_path IS NOT NULL;

-- View application notes
SELECT * FROM application_notes 
ORDER BY created_at DESC;

-- View audit logs for recruitment
SELECT * FROM audit_logs 
WHERE module = 'hr' AND action LIKE '%recruitment%'
ORDER BY created_at DESC;
```

## Common Issues and Solutions

### Issue: Resume section not showing

**Solution:**
1. Clear cache: `php artisan cache:clear`
2. Clear views: `php artisan view:clear`
3. Press Ctrl+F5 in browser
4. Verify file exists in storage directory

### Issue: Download button not working

**Solution:**
1. Verify user has admin/super_admin role
2. Check storage symlink: `php artisan storage:link`
3. Verify file permissions on storage directory
4. Check browser console for errors

### Issue: File upload failing

**Solution:**
1. Check file size (max 5MB)
2. Verify file type (PDF, DOC, DOCX)
3. Check storage directory permissions
4. Verify storage/app/public directory exists

### Issue: Application not showing in list

**Solution:**
1. Verify application status is not "deleted"
2. Check job posting status is "open"
3. Verify user has HR permissions
4. Check database for application record

## Performance Testing

### Load Test: Create 100 Applications

```bash
# Create test applications with resume
for i in {1..100}; do
  # Create application via API or form
done
```

**Expected Result:**
- All applications created successfully
- No timeout errors
- Database queries optimized

### Search Performance

1. Go to applications list
2. Search for common term
3. Check response time

**Expected Result:**
- Search completes in < 1 second
- Results displayed correctly

## Audit Log Verification

Check that audit logs are created for:
- Application submission
- Resume upload
- Status changes
- Resume downloads

```sql
SELECT * FROM audit_logs 
WHERE module = 'hr' 
ORDER BY created_at DESC 
LIMIT 20;
```

## Final Checklist

- [ ] Resume upload works
- [ ] Resume displays in application details
- [ ] Resume download works for HR users
- [ ] Resume download blocked for non-HR users
- [ ] Application status updates work
- [ ] Pipeline visualization displays correctly
- [ ] Convert to employee works
- [ ] Search and filter work
- [ ] Export to PDF works
- [ ] Export to CSV works
- [ ] Audit logs created
- [ ] No errors in browser console
- [ ] No errors in Laravel logs
- [ ] Database records created correctly
- [ ] Files stored in correct directory

## Support

If you encounter any issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console: F12 → Console tab
3. Verify database: Check application records
4. Verify file storage: Check storage/app/public/applications/
5. Clear cache and try again
