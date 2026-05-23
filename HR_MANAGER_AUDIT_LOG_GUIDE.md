# HR Manager Activity Log Guide

## Overview

The HR Manager Activity Log allows HR managers to view all changes made to HR data by administrators and super administrators. This provides transparency and accountability for all HR-related modifications.

## What Can HR Managers See?

HR managers can view activities from the following modules:
- **Payroll**: Payroll generation, approval, payment, reversion
- **Employee**: Employee creation, updates, termination, reactivation
- **Leave**: Leave requests, approvals, rejections, cancellations
- **Recruitment**: Job postings, applications, hiring decisions
- **Performance**: Performance evaluations and reviews
- **Training**: Training programs and enrollments
- **Attendance**: Attendance records and imports
- **Contracts**: Contract creation and renewal

## Accessing the Activity Log

### Method 1: From the HR Menu
1. Login as **HR Manager**
2. Look at the left sidebar under **Staff Management** section
3. Click on **HR** to expand the submenu
4. Scroll down to find **Activity Log**
5. Click on **Activity Log**

### Method 2: Direct URL
```
http://your-domain/hr/audit-logs
```

## Features

### 1. **View All Activities**
- See all changes made by admins and super admins
- Displays date, time, who made the change, action type, module, and details
- Shows IP address of the person who made the change
- Paginated (50 activities per page)

### 2. **Filter Activities**

#### By Module
- Select a specific module to view only changes in that area
- Options: Payroll, Employee, Leave, Recruitment, Performance, Training, Attendance, Contract

#### By Action
- Filter by action type
- Options: Created, Updated, Deleted, Approved, Rejected, Generated, Paid, Reverted, etc.

#### By Date Range
- Select "From" date to see activities from that date onwards
- Select "To" date to see activities up to that date
- Useful for tracking changes in specific time periods

#### By Search
- Search in activity descriptions
- Example: Search for employee code, payroll month, or any keyword

### 3. **Export to CSV**
- Click **Export CSV** button to download filtered activities
- File format: `hr-audit-log-YYYY-MM-DD-HHMMSS.csv`
- Includes: Date, User, Action, Module, Description, IP Address
- Open in Excel or Google Sheets for further analysis

### 4. **Statistics Dashboard**
- **Total Activities**: Total number of activities logged
- **Payroll Changes**: Number of payroll-related changes
- **Employee Changes**: Number of employee-related changes
- **Other Changes**: Number of other HR module changes

## Example Activities

### Payroll Module
```
Date: 23 May 2024 14:30
Changed By: admin@example.com
Action: Generated
Module: Payroll
Details: Payroll generated for EMP001 — January 2024 | Net: 15,000 ETB
```

### Employee Module
```
Date: 22 May 2024 10:15
Changed By: super_admin@example.com
Action: Updated
Module: Employee
Details: Employee profile updated: John Doe
```

### Leave Module
```
Date: 21 May 2024 09:45
Changed By: admin@example.com
Action: Approved
Module: Leave
Details: Leave request #45 approved by user admin@example.com
```

## How to Use the Activity Log

### Scenario 1: Track Payroll Changes
1. Go to Activity Log
2. Select Module: **Payroll**
3. Select Date Range: Last month
4. Click **Filter**
5. Review all payroll changes made by admins

### Scenario 2: Find Employee Updates
1. Go to Activity Log
2. Select Module: **Employee**
3. Enter search term: Employee code or name
4. Click **Filter**
5. See all changes made to that employee

### Scenario 3: Export for Compliance
1. Go to Activity Log
2. Apply desired filters
3. Click **Export CSV**
4. Open in Excel
5. Create reports or share with auditors

### Scenario 4: Monitor Recent Changes
1. Go to Activity Log
2. Select Date Range: Last 7 days
3. Click **Filter**
4. Review all recent changes
5. Identify any unauthorized or suspicious activities

## Understanding the Information

### Date & Time
- Shows when the change was made
- Format: DD MMM YYYY HH:MM:SS
- Example: 23 May 2024 14:30:45

### Changed By
- Shows who made the change (admin or super admin)
- Shows their IP address for security tracking
- Example: admin@example.com (192.168.1.100)

### Action
- Type of change made
- Color-coded for easy identification:
  - **Green**: Created, Generated, Paid, Reactivated, Hired, Completed
  - **Blue**: Updated, Approved, Enrolled
  - **Red**: Deleted, Terminated, Rejected
  - **Yellow**: Reverted
  - **Info**: Approved, Applied

### Module
- Which HR module was affected
- Color-coded:
  - **Red**: Payroll
  - **Blue**: Employee
  - **Yellow**: Leave
  - **Info**: Recruitment, Contract
  - **Success**: Performance
  - **Secondary**: Training
  - **Dark**: Attendance

### Details
- Specific information about the change
- Truncated to 100 characters (hover to see full text)
- Example: "Payroll generated for EMP001 — January 2024 | Net: 15,000 ETB"

## Best Practices

### Regular Monitoring
- Check the activity log weekly or monthly
- Look for unusual patterns or unauthorized changes
- Investigate any suspicious activities

### Compliance & Auditing
- Export activity logs monthly for compliance records
- Archive exported files for regulatory requirements
- Share with auditors when requested

### Tracking Specific Changes
- Use filters to narrow down specific time periods
- Search for employee codes or names
- Combine multiple filters for precise results

### Performance
- Use date range filters to reduce the number of records
- Export large datasets instead of viewing all at once
- Clear filters to reset and start fresh

## Troubleshooting

### Activity Log Not Showing
1. Make sure you're logged in as HR Manager
2. Clear browser cache (Ctrl+F5)
3. Refresh the page
4. Check if activities have been logged (may be empty if no changes made)

### Export Not Working
1. Ensure you have HR Manager access
2. Check that filters are correctly applied
3. Verify sufficient disk space for CSV file
4. Try exporting a smaller date range

### Can't Find Specific Activity
1. Use the search function with keywords
2. Try different date ranges
3. Check the module filter
4. Look for similar activities with different action types

## Security & Privacy

### What You Can See
- All HR-related activities
- Who made the changes
- When changes were made
- What was changed
- IP addresses of users who made changes

### What You Cannot See
- Activities from other modules (Academic, Finance, etc.)
- System-level changes outside HR
- Deleted audit logs
- Activities from other HR managers

### Data Protection
- Activity logs are read-only (cannot be edited)
- All access is logged
- IP addresses help identify unauthorized access
- Regular backups ensure data integrity

## Frequently Asked Questions

**Q: Can I see activities from other HR managers?**
A: No, you can only see activities made by admins and super admins.

**Q: How far back can I view activities?**
A: All activities are stored permanently. You can view activities from any date.

**Q: Can I delete or modify activity logs?**
A: No, activity logs are immutable and cannot be deleted or modified.

**Q: What if I see suspicious activity?**
A: Contact your super admin or system administrator immediately.

**Q: Can I export activities for external auditors?**
A: Yes, use the Export CSV feature to download activities for sharing.

**Q: How often are activities logged?**
A: Activities are logged in real-time as changes are made.

**Q: What if the activity log is empty?**
A: This means no HR activities have been logged yet. Activities will appear as changes are made.

## Support

For issues or questions about the Activity Log:
1. Contact your system administrator
2. Check the troubleshooting section above
3. Review the activity log entries for error messages
4. Export logs for detailed analysis

---

**Last Updated:** May 23, 2026
**Version:** 1.0
**Status:** Active
