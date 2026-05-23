# HR Module Audit Log Documentation

## Overview

The HR Audit Log system provides comprehensive tracking and monitoring of all HR-related activities in the system. It captures every action taken within the HR module, including payroll operations, employee management, leave requests, recruitment, performance evaluations, training, attendance, and contracts.

## Features

### 1. **Comprehensive Activity Tracking**
- **Payroll Operations**: Generation, approval, payment, reversion
- **Employee Management**: Creation, updates, termination, reactivation, status changes
- **Leave Management**: Requests, approvals, rejections, cancellations
- **Recruitment**: Job postings, applications, hiring decisions
- **Performance**: Evaluations, reviews, updates
- **Training**: Program enrollment, completion, updates
- **Attendance**: Records, imports, corrections
- **Contracts**: Creation, renewal, updates

### 2. **Advanced Filtering**
- Filter by **Module** (Payroll, Employee, Leave, Recruitment, etc.)
- Filter by **Action** (Created, Updated, Deleted, Approved, etc.)
- Filter by **Date Range** (From/To dates)
- **Search** in activity descriptions
- Filter by **User** (who performed the action)

### 3. **Export Functionality**
- Export filtered audit logs to **CSV** format
- Includes all relevant information: timestamp, user, action, module, description, IP address
- Useful for compliance, reporting, and archival

### 4. **Statistics Dashboard**
- Total audit logs count
- Payroll-specific actions count
- Employee-related actions count
- Other HR actions count

## Accessing the HR Audit Log

### Route
```
GET /audit-logs/hr
```

### Navigation
1. Go to **Settings** (Super Admin menu)
2. Click **Audit Logs**
3. Click **HR Module Logs** button

### URL
```
http://your-domain/audit-logs/hr
```

## Using the Audit Log

### Basic Viewing
1. Navigate to the HR Audit Log page
2. All HR-related activities are displayed in reverse chronological order (newest first)
3. Each entry shows:
   - **Time**: When the action occurred
   - **User**: Who performed the action
   - **Action**: Type of action (Created, Updated, Approved, etc.)
   - **Module**: Which HR module was affected
   - **Description**: Detailed information about the action
   - **IP Address**: IP address from which the action was performed

### Filtering Activities

#### By Module
1. Select a module from the **Module** dropdown
2. Options include: Payroll, Employee, Leave, Recruitment, Performance, Training, Attendance, Contract
3. Click **Filter** to apply

#### By Action
1. Select an action from the **Action** dropdown
2. Options include: Created, Updated, Deleted, Approved, Rejected, Generated, Paid, etc.
3. Click **Filter** to apply

#### By Date Range
1. Enter a **From** date in the date picker
2. Enter a **To** date in the date picker
3. Click **Filter** to apply

#### By Search
1. Enter keywords in the **Search** field
2. Searches within activity descriptions
3. Click **Filter** to apply

#### Combining Filters
You can combine multiple filters:
- Example: Show all "Payroll" module "Approved" actions from "2024-01-01" to "2024-01-31"
- Example: Search for "employee code ABC123" in "Employee" module

### Resetting Filters
Click the **Reset** button to clear all filters and view all HR audit logs.

### Exporting Data

#### Export Current View
1. Apply desired filters
2. Click **Export CSV** button
3. A CSV file will be downloaded with the filtered results

#### CSV Format
The exported CSV includes:
- Date (YYYY-MM-DD HH:MM:SS format)
- User (name of the person who performed the action)
- Action (type of action)
- Module (HR module affected)
- Description (detailed information)
- IP Address (source IP)

#### Using Exported Data
- Import into Excel or Google Sheets for analysis
- Create custom reports
- Archive for compliance purposes
- Share with auditors or management

## Audit Log Entries by Module

### Payroll Module
**Actions Logged:**
- `generated` - Payroll generated for employees
- `updated` - Payroll approved or marked as paid
- `reverted` - Payroll reverted to draft status

**Example Description:**
```
Payroll generated for EMP001 — January 2024 | Net: 15,000 ETB
Payroll #123 approved by user admin@example.com
Payroll #123 marked as paid
Payroll #123 reverted to draft
```

### Employee Module
**Actions Logged:**
- `created` - New employee created
- `updated` - Employee profile updated
- `deleted` - Employee deleted
- `terminated` - Employee terminated
- `reactivated` - Employee reactivated

**Example Description:**
```
Employee created: John Doe (EMP001)
Employee profile updated: John Doe
Employee terminated: John Doe
Employee reactivated: John Doe
```

### Leave Module
**Actions Logged:**
- `created` - Leave request submitted
- `updated` - Leave request approved or rejected
- `deleted` - Leave request cancelled

**Example Description:**
```
Leave request #45 submitted by employee EMP001 — Annual Leave 5 day(s)
Leave request #45 approved by user manager@example.com
Leave request #45 rejected by user manager@example.com
Leave request #45 cancelled by user EMP001
```

### Recruitment Module
**Actions Logged:**
- `created` - Job posting created
- `updated` - Job posting updated
- `applied` - Application submitted
- `hired` - Candidate hired
- `rejected` - Candidate rejected

**Example Description:**
```
Job posting created: Senior Developer
Application submitted by candidate John Smith
Candidate John Smith hired for Senior Developer position
Candidate Jane Doe rejected for Senior Developer position
```

### Performance Module
**Actions Logged:**
- `created` - Performance evaluation created
- `updated` - Performance evaluation updated
- `completed` - Performance evaluation completed

**Example Description:**
```
Performance evaluation created for employee EMP001
Performance evaluation updated for employee EMP001
Performance evaluation completed for employee EMP001
```

### Training Module
**Actions Logged:**
- `created` - Training program created
- `enrolled` - Employee enrolled in training
- `completed` - Training completed

**Example Description:**
```
Training program created: Leadership Development
Employee EMP001 enrolled in Leadership Development
Employee EMP001 completed Leadership Development
```

### Attendance Module
**Actions Logged:**
- `created` - Attendance record created
- `updated` - Attendance record updated
- `imported` - Attendance data imported

**Example Description:**
```
Attendance record created for employee EMP001 on 2024-01-15
Attendance data imported: 50 records processed
```

### Contract Module
**Actions Logged:**
- `created` - Contract created
- `updated` - Contract updated
- `renewed` - Contract renewed

**Example Description:**
```
Contract created for employee EMP001
Contract renewed for employee EMP001
```

## Compliance and Audit Trail

### Data Retention
- All audit logs are permanently stored in the database
- No automatic deletion or archival (manual cleanup can be configured)
- Logs include IP addresses for security tracking

### Compliance Features
- **User Accountability**: Every action is tied to a specific user
- **Timestamp Accuracy**: All actions are timestamped with server time
- **IP Tracking**: Source IP address is recorded for each action
- **Immutable Records**: Audit logs cannot be modified or deleted by regular users
- **Export for Audits**: CSV export for external auditors or compliance reviews

### Security Considerations
- Only Super Admin users can access audit logs
- Audit logs are read-only (cannot be edited or deleted)
- All access to audit logs is itself logged
- IP addresses help identify unauthorized access patterns

## Best Practices

### Regular Monitoring
1. Review HR audit logs weekly or monthly
2. Look for unusual patterns or unauthorized actions
3. Investigate any suspicious activities

### Compliance Reporting
1. Export audit logs monthly for compliance records
2. Archive exported files for regulatory requirements
3. Share with auditors when requested

### Troubleshooting
1. If an action is missing from logs, check if it was performed by a system process
2. Use date range filters to narrow down specific time periods
3. Search for employee codes or names to find related activities

### Performance
- Audit logs are paginated (50 per page) for performance
- Use filters to reduce the number of records displayed
- Export large datasets instead of viewing all at once

## API Integration

### Logging Custom HR Actions

To log custom HR actions in your code:

```php
use App\Models\AuditLog;

AuditLog::log(
    'action_name',      // e.g., 'created', 'updated', 'approved'
    'module_name',      // e.g., 'payroll', 'employee', 'leave'
    'description'       // e.g., 'Payroll generated for EMP001'
);
```

### Example Usage

```php
// In a service or controller
AuditLog::log(
    'generated',
    'payroll',
    "Payroll generated for {$employee->employee_code} — {$month} | Net: {$netPay} ETB"
);
```

### Automatic Logging
The following services automatically log HR activities:
- `PayrollService` - Payroll operations
- `LeaveService` - Leave management
- `EmployeeProfileService` - Employee profile changes
- `TrainingController` - Training activities
- `RecruitmentController` - Recruitment activities
- `PerformanceController` - Performance evaluations

## Troubleshooting

### Audit Logs Not Appearing
1. Verify the action was performed by a logged-in user
2. Check that the module name matches one of the tracked modules
3. Clear browser cache (Ctrl+F5)
4. Clear Laravel cache: `php artisan cache:clear`

### Export Not Working
1. Ensure you have Super Admin access
2. Check that filters are correctly applied
3. Verify sufficient disk space for CSV file
4. Try exporting a smaller date range

### Performance Issues
1. Use date range filters to limit results
2. Export data instead of viewing large datasets
3. Archive old logs if needed
4. Contact system administrator for database optimization

## Future Enhancements

Potential improvements to the audit log system:
- Real-time audit log dashboard with live updates
- Advanced analytics and reporting
- Automated alerts for suspicious activities
- Audit log retention policies
- Integration with external logging systems
- Role-based audit log access
- Detailed change tracking (before/after values)
- Audit log search with advanced query syntax

## Support

For issues or questions about the HR Audit Log system:
1. Contact your system administrator
2. Check the troubleshooting section above
3. Review the audit log entries for error messages
4. Export logs for detailed analysis

---

**Last Updated:** May 23, 2026
**Version:** 1.0
**Status:** Active
