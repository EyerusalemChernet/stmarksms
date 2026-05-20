# Recruitment Module - Complete Implementation Guide

## Overview
The recruitment module has been fully implemented with resume upload functionality. Job applicants can now attach their resume/CV when applying for positions, and HR/SuperAdmin users can download and review these files.

## Features Implemented

### 1. Job Postings Management
- Create, edit, and delete job postings
- Set employment type (full-time, part-time, contract, intern)
- Define vacancies and application deadlines
- Track application counts per posting
- Export postings to PDF/CSV

### 2. Job Applications with Resume Upload
- Applicants can submit applications with resume attachment
- Resume file validation (PDF, DOC, DOCX only)
- File size limit: 5MB
- Resume stored in: `storage/app/public/applications/{job_posting_id}/{timestamp}_{filename}`

### 3. Application Pipeline Management
- Track applications through pipeline: Applied → Shortlisted → Interviewed → Hired/Rejected
- Update application status with optional notes
- Set interview dates
- Visual pipeline display showing application progress

### 4. Resume Management
- HR/SuperAdmin can download applicant resumes
- Resume download restricted to authorized users
- Resume filename and download link displayed in application details
- Audit logging for all resume downloads

### 5. Applicant to Employee Conversion
- Convert hired applicants directly to employees
- Pre-fills employee form with applicant data
- Maintains data consistency

## File Structure

### Models
- `app/Models/JobPosting.php` - Job posting model with relationships
- `app/Models/JobApplication.php` - Job application model with resume accessors
- `app/Models/ApplicationNote.php` - Notes/history for applications

### Controllers
- `app/Http/Controllers/SupportTeam/RecruitmentController.php` - Main recruitment controller

### Views
- `resources/views/pages/hr/recruitment/postings.blade.php` - Job postings list
- `resources/views/pages/hr/recruitment/posting_edit.blade.php` - Create/edit posting
- `resources/views/pages/hr/recruitment/applications.blade.php` - Applications list
- `resources/views/pages/hr/recruitment/application_create.blade.php` - Application form with resume upload
- `resources/views/pages/hr/recruitment/application_show.blade.php` - Application details with resume display

### Routes
```php
// Job Postings
GET    /hr/recruitment/postings                    - List postings
POST   /hr/recruitment/postings                    - Create posting
GET    /hr/recruitment/postings/{id}/edit          - Edit posting
PUT    /hr/recruitment/postings/{id}               - Update posting
DELETE /hr/recruitment/postings/{id}               - Delete posting

// Job Applications
GET    /hr/recruitment/applications                - List applications
GET    /hr/recruitment/applications/create/{id?}   - Create application form
POST   /hr/recruitment/applications                - Submit application
GET    /hr/recruitment/applications/{id}           - View application details
POST   /hr/recruitment/applications/{id}/status    - Update application status
POST   /hr/recruitment/applications/{id}/note      - Add note to application
GET    /hr/recruitment/applications/{id}/convert   - Convert to employee

// Resume Download (HR/SuperAdmin only)
GET    /hr/recruitment/applications/{id}/download-resume - Download resume
```

## Database Schema

### job_postings table
```
id, title, department_id, position_id, description, requirements, 
employment_type, vacancies, deadline, status, created_by, created_at, updated_at
```

### job_applications table
```
id, job_posting_id, first_name, last_name, email, phone, address, 
resume_path, cover_letter, status, applied_at, interview_date, 
reviewed_by, created_at, updated_at
```

### application_notes table
```
id, application_id, user_id, status_changed_to, note, created_at, updated_at
```

## Resume Upload Process

### 1. Application Submission
- User selects job posting
- Fills in personal information
- Attaches resume file (PDF, DOC, DOCX, max 5MB)
- Optionally adds cover letter
- Submits application

### 2. File Storage
- File stored in: `storage/app/public/applications/{job_posting_id}/{timestamp}_{filename}`
- Path stored in `job_applications.resume_path` column
- Audit log created for submission

### 3. Resume Access
- HR/SuperAdmin can view application details
- Resume filename displayed
- Download button available for authorized users
- Download creates audit log entry

## Resume Download Authorization

The `downloadResume` method in RecruitmentController checks:
```php
if (!auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
    abort(403, 'Unauthorized');
}
```

Only users with `super_admin` or `admin` roles can download resumes.

## JobApplication Model Methods

### Accessors
- `getFullNameAttribute()` - Returns "First Last"
- `getResumeFileNameAttribute()` - Returns filename from path
- `getResumeUrlAttribute()` - Returns asset URL for resume
- `getHasResumeAttribute()` - Checks if file exists
- `getStatusBadgeAttribute()` - Returns badge color for status

### Helper Methods
- `statusBadgeClass()` - Returns CSS class for status badge
- `isHired()` - Checks if application is hired
- `isRejected()` - Checks if application is rejected
- `hasResume()` - Checks if resume exists and is accessible

### Scopes
- `status($status)` - Filter by status
- `pending()` - Get pending applications
- `shortlisted()` - Get shortlisted applications
- `rejected()` - Get rejected applications
- `hired()` - Get hired applications

## Validation Rules

### Application Submission
```php
'job_posting_id' => 'required|exists:job_postings,id',
'first_name'     => 'required|string|max:80',
'last_name'      => 'required|string|max:80',
'email'          => 'nullable|email|max:100',
'phone'          => 'nullable|string|max:20',
'address'        => 'nullable|string|max:255',
'resume'         => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB
'cover_letter'   => 'nullable|string',
```

## Audit Logging

All recruitment actions are logged:
- Application submission (with/without resume)
- Status changes
- Resume downloads
- Job posting creation/updates/deletion

## Testing Workflow

### 1. Create Job Posting
- Navigate to HR → Recruitment → Job Postings
- Click "Add Job Posting"
- Fill in details and save

### 2. Submit Application with Resume
- Navigate to HR → Recruitment → Applications
- Click "Add Application"
- Select job posting
- Fill in applicant details
- Attach resume file
- Submit

### 3. View Application and Download Resume
- Navigate to HR → Recruitment → Applications
- Click on application row
- View applicant details
- See resume section with download button
- Click download to get resume file

### 4. Update Application Status
- In application details page
- Select new status from dropdown
- Optionally set interview date
- Add note about status change
- Click Update

## Troubleshooting

### Resume Not Showing
1. Clear Laravel cache: `php artisan cache:clear`
2. Clear view cache: `php artisan view:clear`
3. Clear browser cache: Ctrl+F5
4. Verify file exists in `storage/app/public/applications/`

### Download Not Working
1. Verify user has `super_admin` or `admin` role
2. Check file permissions on storage directory
3. Verify `storage/app/public` is symlinked to `public/storage`
4. Run: `php artisan storage:link`

### File Upload Failing
1. Check file size (max 5MB)
2. Verify file type (PDF, DOC, DOCX only)
3. Check storage directory permissions
4. Verify `storage/app/public` directory exists

## Performance Considerations

- Applications list is paginated (20 per page)
- Eager loading used for relationships (jobPosting, notes, reviewedBy)
- Indexes on job_posting_id, status, applied_at for faster queries
- Resume files stored outside database for better performance

## Security Considerations

- Resume downloads restricted to HR/SuperAdmin only
- File upload validated for type and size
- File stored outside web root (in storage directory)
- Audit logging for all resume access
- CSRF protection on all forms
- Input validation on all fields

## Future Enhancements

- Email notifications for application status changes
- Bulk import of applications
- Advanced filtering and search
- Application scoring/rating system
- Interview scheduling integration
- Offer letter generation
- Background check integration
- Resume parsing/OCR

## Git History

Latest commits:
- `f10ce82` - Fix recruitment module: add PIPELINE constant and resume display
- `22ffd7a` - Fix User model import in JobApplication
- `7597d6d` - Add resume download route and authorization
- `e9c9979` - Add resume upload to application form
- `c072d9b` - Update RecruitmentController with resume handling
- `7f29481` - Add resume accessors to JobApplication model

## Deployment Checklist

- [ ] All code committed and pushed
- [ ] Database migrations run
- [ ] Storage directory permissions set correctly
- [ ] Storage symlink created: `php artisan storage:link`
- [ ] Cache cleared: `php artisan cache:clear`
- [ ] View cache cleared: `php artisan view:clear`
- [ ] Browser cache cleared: Ctrl+F5
- [ ] Test application submission with resume
- [ ] Test resume download as HR user
- [ ] Test resume download as non-HR user (should fail)
- [ ] Verify audit logs created
- [ ] Monitor error logs for issues
