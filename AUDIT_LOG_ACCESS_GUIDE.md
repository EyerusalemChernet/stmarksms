# How to Access the HR Audit Log

## Quick Start

### Step 1: Clear Your Browser Cache
Press **Ctrl+F5** (or **Cmd+Shift+R** on Mac) to clear the browser cache and reload the page.

### Step 2: Navigate to Audit Logs
1. Login as **Super Admin**
2. Look at the left sidebar menu
3. Under the **Settings** section, you should see **"Audit Logs"** link
4. Click on **"Audit Logs"**

### Step 3: View HR Module Logs
1. On the Audit Logs page, click the **"HR Module Logs"** button (blue button at top right)
2. You will see the HR-specific audit log with filtering options

## Direct URLs

### All Audit Logs
```
http://your-domain/super_admin/audit-logs
```

### HR Module Audit Logs
```
http://your-domain/super_admin/audit-logs/hr
```

## Menu Navigation Path

```
Settings (sidebar section)
  └─ Audit Logs (link)
      └─ HR Module Logs (button on page)
```

## What You Should See

### On the Audit Logs Page
- A table with all system audit logs
- A blue button labeled **"HR Module Logs"** at the top right
- A link to go back to Settings

### On the HR Module Logs Page
- Advanced filtering options:
  - Module filter (Payroll, Employee, Leave, etc.)
  - Action filter (Created, Updated, Approved, etc.)
  - Date range filter
  - Search box
- A table showing HR-related activities
- An **"Export CSV"** button to download logs
- Statistics showing total logs and breakdown by module

## Troubleshooting

### I Don't See "Audit Logs" in the Menu
1. Make sure you're logged in as **Super Admin**
2. Clear browser cache: **Ctrl+F5**
3. Refresh the page
4. Check if you're in the correct user role

### I See "Audit Logs" but No HR Module Logs Button
1. Clear browser cache: **Ctrl+F5**
2. Clear Laravel cache: Run `php artisan cache:clear` in terminal
3. Refresh the page

### The Page Shows "No audit logs yet"
This is normal if no HR activities have been logged yet. The audit log will start recording activities once:
- Payroll is generated
- Employees are created/updated
- Leave requests are submitted
- Other HR actions are performed

## Features Available

### Filtering
- **By Module**: Select Payroll, Employee, Leave, Recruitment, Performance, Training, Attendance, or Contract
- **By Action**: Select Created, Updated, Deleted, Approved, Rejected, Generated, Paid, etc.
- **By Date Range**: Select From and To dates
- **By Search**: Search in activity descriptions

### Export
- Click **"Export CSV"** to download filtered logs
- File format: `hr-audit-log-YYYY-MM-DD-HHMMSS.csv`
- Open in Excel or Google Sheets

### Statistics
- View total logs count
- View payroll-specific actions count
- View employee-related actions count
- View other HR actions count

## Example Activities Logged

### Payroll
- Payroll generated for employees
- Payroll approved
- Payroll marked as paid
- Payroll reverted to draft

### Employee
- Employee created
- Employee profile updated
- Employee terminated
- Employee reactivated

### Leave
- Leave request submitted
- Leave request approved
- Leave request rejected
- Leave request cancelled

### Other Modules
- Recruitment applications
- Performance evaluations
- Training enrollments
- Attendance records
- Contract renewals

## Need Help?

If you still can't see the audit log:
1. Make sure you're logged in as Super Admin
2. Clear browser cache (Ctrl+F5)
3. Run `php artisan cache:clear` in terminal
4. Refresh the page
5. Check the browser console for errors (F12)

---

**Last Updated:** May 23, 2026
