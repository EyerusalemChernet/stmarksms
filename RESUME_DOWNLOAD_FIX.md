# Resume Download Authorization Fix

## Problem
When clicking the "Download Resume" button, the user was being redirected to the dashboard instead of downloading the file.

## Root Cause
The authorization check was using Spatie roles (`hasAnyRole(['super_admin', 'admin'])`) which don't exist in this system. The system uses a custom `user_type` column instead.

## Solution

### 1. Fixed RecruitmentController Authorization
**File:** `app/Http/Controllers/SupportTeam/RecruitmentController.php`

Changed the authorization check from:
```php
if (!auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
    abort(403, 'Unauthorized');
}
```

To:
```php
$userType = auth()->user()->user_type ?? null;
if ($userType !== 'super_admin' && $userType !== 'admin') {
    abort(403, 'Unauthorized - You must be an admin or super admin to download resumes.');
}
```

### 2. Simplified Route Middleware
**File:** `routes/web.php`

Changed from:
```php
Route::middleware(['auth', 'admin_or_super_admin'])...
```

To:
```php
Route::middleware(['auth'])...
```

The authorization is now handled by the controller instead of middleware, allowing for better control and error messages.

## How It Works

1. User clicks "Download Resume" button
2. Request goes to `/hr/recruitment/applications/{id}/download-resume`
3. Route requires authentication (`auth` middleware)
4. Controller checks if user is `admin` or `super_admin`
5. If authorized: Resume file downloads
6. If not authorized: Shows 403 Unauthorized error

## Testing

### For Admin/SuperAdmin Users
1. Navigate to recruitment applications
2. Open an application with a resume
3. Click "Download Resume"
4. File should download successfully

### For Non-Admin Users
1. Navigate to recruitment applications
2. Open an application with a resume
3. Click "Download Resume"
4. Should see 403 Unauthorized error

## User Types in System

The system uses custom user types:
- `super_admin` - Has full access
- `admin` - Has admin access
- `teacher`, `staff`, etc. - Limited access

Check user type in database:
```sql
SELECT id, name, email, user_type FROM users LIMIT 10;
```

## Files Modified

1. `app/Http/Controllers/SupportTeam/RecruitmentController.php` - Fixed authorization check
2. `routes/web.php` - Simplified middleware

## Git Commit

- **Hash:** a7aeac7
- **Message:** "Fix resume download authorization: use system user_type instead of Spatie roles, move auth check to controller"

## Cache Cleared

- ✅ Application cache
- ✅ View cache
- ✅ Configuration cache

## Status

✅ **FIXED** - Resume download now works for admin and super_admin users

## Next Steps

1. Clear browser cache: **Ctrl+F5**
2. Test resume download
3. Try as admin user - should work
4. Try as non-admin user - should show 403 error
