# Requirements Document

## Introduction

This document defines requirements for a comprehensive School Finance Management System
built on top of the existing Laravel 8 SMS application. The system extends the partial
fee management module already in place (FeeCategory, FeeStructure, StudentFeeInvoice,
FeePayment) and adds payroll, expense management, financial reporting, role-based access
control for an Accountant role, notifications, and audit logging. All monetary values
are in ETB (Ethiopian Birr). The UI uses Bootstrap 4 with Bootstrap Icons.

---

## Glossary

- **Finance_System**: The complete school finance management module described in this document
- **Fee_Manager**: The controller and service layer responsible for student fee operations
- **Payroll_Manager**: The controller and service layer responsible for staff salary operations
- **Expense_Manager**: The controller and service layer responsible for school expense operations
- **Report_Engine**: The service responsible for generating financial reports and PDF documents
- **Audit_Logger**: The service that records all create, update, and delete actions on financial records
- **Notification_Service**: The service that dispatches fee reminders, payment confirmations, and salary notifications
- **Admin**: A user with the `admin` or `super_admin` role — has full access to all finance features
- **Accountant**: A user with the new `accountant` role — has access to finance features only
- **Student**: A user with the `student` role
- **Parent**: A user with the `my_parent` role
- **HR_Manager**: A user with the `hr_manager` role — retains existing access
- **Invoice**: A StudentFeeInvoice record linking a student to a fee structure with payment tracking
- **Payslip**: A generated document summarising a staff member's monthly salary calculation
- **Expense**: A recorded school expenditure with category, amount, date, and optional receipt file
- **Chapa**: The existing third-party payment gateway integrated at `ChapaController`
- **Session**: An academic year string (e.g. "2024/2025") used to scope fee structures and invoices

---

## Requirements

### Requirement 1: Fee Category Management

**User Story:** As an Admin or Accountant, I want to create and manage fee categories,
so that fees can be organised by type (e.g. tuition, transport, library).

#### Acceptance Criteria

1. THE Fee_Manager SHALL allow creating a fee category with a unique code, name, and optional description.
2. THE Fee_Manager SHALL allow updating the name, description, and active status of an existing fee category.
3. THE Fee_Manager SHALL allow deleting a fee category that has no associated fee structures.
4. IF a delete is attempted on a fee category that has associated fee structures, THEN THE Fee_Manager SHALL reject the deletion and return a descriptive error message.
5. THE Fee_Manager SHALL list all fee categories with their associated structure count.

---

### Requirement 2: Fee Structure Management

**User Story:** As an Admin or Accountant, I want to define fee structures per class and
academic session, so that the correct fee amounts are applied to the right students.

#### Acceptance Criteria

1. THE Fee_Manager SHALL allow creating a fee structure with a fee category, class, session, amount in ETB, and number of allowed installments.
2. THE Fee_Manager SHALL allow filtering fee structures by session and class.
3. THE Fee_Manager SHALL prevent duplicate fee structures for the same category, class, and session combination.
4. IF a duplicate fee structure is submitted, THEN THE Fee_Manager SHALL reject the request and return a descriptive error message.
5. THE Fee_Manager SHALL allow deleting a fee structure that has no assigned invoices.

---

### Requirement 3: Fee Assignment to Students

**User Story:** As an Admin or Accountant, I want to assign fee structures to individual
students or entire classes, so that invoices are generated automatically.

#### Acceptance Criteria

1. WHEN a fee structure is assigned to a class, THE Fee_Manager SHALL create an Invoice for every active, non-graduated student in that class who does not already have an Invoice for that fee structure and session.
2. WHEN a fee structure is assigned to an individual student, THE Fee_Manager SHALL create one Invoice for that student if one does not already exist for that fee structure and session.
3. THE Fee_Manager SHALL set the Invoice due date to 30 days from the assignment date by default.
4. THE Fee_Manager SHALL set the initial Invoice status to "unpaid" with balance equal to the fee structure amount.

---

### Requirement 4: Discounts, Scholarships, and Penalties

**User Story:** As an Admin or Accountant, I want to apply discounts, scholarships, and
penalties to individual invoices, so that special cases are handled accurately.

#### Acceptance Criteria

1. WHEN a discount is applied to an Invoice, THE Fee_Manager SHALL require a numeric discount amount and a mandatory reason, then recalculate the net amount as (original_amount − discount + fine).
2. WHEN a fine (penalty) is applied to an Invoice, THE Fee_Manager SHALL require a numeric fine amount and a mandatory reason, then recalculate the net amount as (original_amount − discount + fine).
3. THE Fee_Manager SHALL prevent the net amount from falling below zero after applying a discount.
4. WHEN the net amount is recalculated, THE Fee_Manager SHALL update the Invoice balance and synchronise the payment status.

---

### Requirement 5: Payment Recording and Status Tracking

**User Story:** As an Admin or Accountant, I want to record fee payments against invoices,
so that payment status is always accurate.

#### Acceptance Criteria

1. WHEN a payment is recorded against an Invoice, THE Fee_Manager SHALL require a payment amount, payment method, and optional transaction reference.
2. THE Fee_Manager SHALL prevent recording a payment amount greater than the current Invoice balance.
3. WHEN a payment is saved, THE Fee_Manager SHALL update the Invoice's amount_paid, balance, and status fields: status becomes "paid" when balance reaches zero, "partial" when balance is positive but a payment exists, and "unpaid" when no payments exist.
4. THE Fee_Manager SHALL assign a unique receipt number to every recorded payment.
5. THE Fee_Manager SHALL record the identity of the staff member who collected the payment.

---

### Requirement 6: Chapa Online Payment Integration

**User Story:** As a Student or Parent, I want to pay fees online via Chapa, so that I
can settle balances without visiting the school.

#### Acceptance Criteria

1. WHEN a Student or Parent initiates an online payment for an Invoice, THE Fee_Manager SHALL call the existing Chapa integration to create a payment session for the Invoice balance amount in ETB.
2. WHEN Chapa returns a successful callback, THE Fee_Manager SHALL automatically record a FeePayment against the corresponding Invoice and update the Invoice status.
3. WHEN Chapa returns a failed or pending callback, THE Fee_Manager SHALL log the transaction with status "failed" or "pending" without updating the Invoice balance.
4. THE Fee_Manager SHALL store the Chapa transaction reference on the FeePayment record.

---

### Requirement 7: Refund Processing

**User Story:** As an Admin or Accountant, I want to process refunds on overpaid or
cancelled invoices, so that students receive accurate credit.

#### Acceptance Criteria

1. WHEN a refund is initiated on a paid Invoice, THE Fee_Manager SHALL require a refund amount not exceeding the total amount paid and a mandatory reason.
2. WHEN a refund is saved, THE Fee_Manager SHALL create a negative-amount FeePayment record, update the Invoice balance, and re-synchronise the Invoice status.
3. THE Fee_Manager SHALL record the identity of the staff member who processed the refund.

---

### Requirement 8: Invoice and Receipt PDF Generation

**User Story:** As an Admin, Accountant, Student, or Parent, I want to download invoices
and payment receipts as PDFs, so that I have printable financial documents.

#### Acceptance Criteria

1. WHEN an invoice PDF is requested, THE Report_Engine SHALL generate a PDF containing the student name, class, session, fee category, original amount, discount, fine, net amount, amount paid, balance, status, and due date.
2. WHEN a receipt PDF is requested, THE Report_Engine SHALL generate a PDF containing the receipt number, student name, payment amount in ETB, payment method, transaction reference, payment date, and the name of the collecting staff member.
3. THE Report_Engine SHALL render PDFs using a Laravel-compatible PDF library (e.g. DomPDF via barryvdh/laravel-dompdf).

---

### Requirement 9: Salary Structure Management

**User Story:** As an Admin or HR_Manager, I want to define salary structures for staff,
so that payroll calculations are consistent and auditable.

#### Acceptance Criteria

1. THE Payroll_Manager SHALL allow creating a salary structure for a staff member with components: basic salary, housing allowance, transport allowance, and other allowances, all in ETB.
2. THE Payroll_Manager SHALL allow updating salary structure components for a staff member.
3. THE Payroll_Manager SHALL allow defining deduction rules per staff member: income tax (percentage), loan repayment (fixed amount), and absence deduction (amount per absent day).
4. THE Payroll_Manager SHALL store one active salary structure per staff member at a time.

---

### Requirement 10: Monthly Payroll Processing

**User Story:** As an Admin or HR_Manager, I want to process monthly payroll for all
staff, so that net salaries are calculated and payslips are generated.

#### Acceptance Criteria

1. WHEN monthly payroll is processed for a given month and year, THE Payroll_Manager SHALL calculate gross salary as (basic + housing allowance + transport allowance + other allowances + bonus).
2. WHEN monthly payroll is processed, THE Payroll_Manager SHALL calculate total deductions as (income_tax_percentage × gross + loan_repayment + absence_days × absence_deduction_rate).
3. WHEN monthly payroll is processed, THE Payroll_Manager SHALL calculate net salary as (gross − total deductions).
4. THE Payroll_Manager SHALL prevent processing payroll for the same staff member, month, and year more than once unless the previous record is explicitly voided.
5. WHEN payroll is processed, THE Payroll_Manager SHALL create a Payroll record for each staff member containing gross, deductions breakdown, net salary, month, year, and processing date.

---

### Requirement 11: Payslip Generation

**User Story:** As an Admin, HR_Manager, or Staff member, I want to download a payslip
PDF, so that I have a formal record of my monthly salary.

#### Acceptance Criteria

1. WHEN a payslip PDF is requested for a Payroll record, THE Report_Engine SHALL generate a PDF containing staff name, position, department, month, year, gross salary, itemised allowances, itemised deductions, and net salary in ETB.
2. THE Report_Engine SHALL format all monetary values with the ETB currency symbol.

---

### Requirement 12: Expense Management

**User Story:** As an Admin or Accountant, I want to record, edit, and delete school
expenses, so that all outgoings are tracked.

#### Acceptance Criteria

1. THE Expense_Manager SHALL allow creating an expense record with a category, title, amount in ETB, expense date, optional description, and optional receipt file upload.
2. THE Expense_Manager SHALL allow updating any field of an existing expense record.
3. THE Expense_Manager SHALL allow deleting an expense record and its associated receipt file.
4. THE Expense_Manager SHALL allow filtering expenses by category, date range, and recurring status.
5. WHERE an expense is marked as recurring, THE Expense_Manager SHALL store the recurrence interval (monthly, quarterly, annually).
6. THE Expense_Manager SHALL accept receipt file uploads in PDF, JPG, or PNG format with a maximum size of 5 MB.

---

### Requirement 13: Expense Categories

**User Story:** As an Admin or Accountant, I want to manage expense categories, so that
expenses are consistently classified.

#### Acceptance Criteria

1. THE Expense_Manager SHALL allow creating an expense category with a unique name and optional description.
2. THE Expense_Manager SHALL allow updating and deleting expense categories.
3. IF a delete is attempted on an expense category that has associated expenses, THEN THE Expense_Manager SHALL reject the deletion and return a descriptive error message.

---

### Requirement 14: Financial Reports

**User Story:** As an Admin or Accountant, I want to view and export financial reports,
so that I can monitor the school's financial health.

#### Acceptance Criteria

1. THE Report_Engine SHALL generate an income report showing total fee payments collected, grouped by fee category and date range.
2. THE Report_Engine SHALL generate an expense report showing total expenses, grouped by expense category and date range.
3. THE Report_Engine SHALL generate a profit/loss summary showing total income minus total expenses for a selected date range.
4. THE Report_Engine SHALL generate an outstanding fees report listing all Invoices with status "unpaid" or "partial", including student name, class, fee category, net amount, amount paid, and balance.
5. THE Report_Engine SHALL generate a salary report showing total payroll cost per month, with a breakdown per staff member.
6. THE Report_Engine SHALL allow exporting each report as a PDF.
7. THE Report_Engine SHALL display all monetary values in ETB on all reports.

---

### Requirement 15: Finance Dashboard

**User Story:** As an Admin or Accountant, I want a finance dashboard with summary
charts, so that I can see key financial metrics at a glance.

#### Acceptance Criteria

1. THE Finance_System SHALL display a dashboard showing: total fees collected this month, total outstanding balance, total expenses this month, and total payroll cost this month.
2. THE Finance_System SHALL render a monthly income vs. expense bar chart for the current academic year using a JavaScript charting library compatible with Bootstrap 4.
3. THE Finance_System SHALL render a fee payment status pie chart (paid, partial, unpaid) for the current session.
4. WHEN the dashboard data is loaded, THE Finance_System SHALL query only the current academic session and current calendar month for summary figures.

---

### Requirement 16: Role-Based Access Control

**User Story:** As a system administrator, I want finance routes to be protected by role,
so that only authorised users can access financial data.

#### Acceptance Criteria

1. THE Finance_System SHALL introduce an `accountant` role with access restricted to fee management, expense management, payroll viewing, and financial reports.
2. THE Finance_System SHALL allow Admin and HR_Manager roles to access all finance features.
3. THE Finance_System SHALL allow Student and Parent roles to view only their own invoices and make online payments.
4. THE Finance_System SHALL protect all finance routes with Laravel middleware that enforces the role checks defined above.
5. IF a user without the required role attempts to access a finance route, THEN THE Finance_System SHALL redirect the user to the dashboard with an "Unauthorised" error message.

---

### Requirement 17: Fee Reminder Notifications

**User Story:** As an Admin or Accountant, I want the system to send fee reminders to
students and parents, so that overdue payments are reduced.

#### Acceptance Criteria

1. WHEN an Invoice due date passes and the Invoice status is "unpaid" or "partial", THE Notification_Service SHALL send a fee reminder notification to the associated student and parent.
2. THE Notification_Service SHALL send fee reminders via the existing in-app messaging system.
3. THE Notification_Service SHALL record the date and time of each reminder sent against the Invoice.

---

### Requirement 18: Payment Confirmation Notifications

**User Story:** As a Student or Parent, I want to receive a confirmation when a payment
is recorded, so that I know my payment was processed.

#### Acceptance Criteria

1. WHEN a FeePayment is successfully recorded, THE Notification_Service SHALL send a payment confirmation notification to the associated student and parent containing the receipt number, amount paid in ETB, and remaining balance.

---

### Requirement 19: Salary Notifications

**User Story:** As a staff member, I want to be notified when my monthly payslip is
ready, so that I can review my salary details.

#### Acceptance Criteria

1. WHEN a Payroll record is created for a staff member, THE Notification_Service SHALL send a salary notification to that staff member containing the net salary amount in ETB and the month/year.

---

### Requirement 20: Audit Logging

**User Story:** As an Admin, I want all financial create, update, and delete actions to
be logged, so that I can trace any changes to financial records.

#### Acceptance Criteria

1. WHEN any financial record (Invoice, FeePayment, Payroll, Expense, SalaryStructure) is created, updated, or deleted, THE Audit_Logger SHALL record the action type, the affected model and record ID, the user who performed the action, and the timestamp.
2. THE Audit_Logger SHALL store the before and after values for update actions.
3. THE Finance_System SHALL provide an audit log view accessible to Admin only, filterable by date range, user, and action type.
4. THE Audit_Logger SHALL use the existing AuditLog model already present in the application.

---

### Requirement 21: Search and Filter

**User Story:** As an Admin or Accountant, I want to search and filter records across all finance list pages, so that I can quickly locate specific invoices, payments, expenses, and payroll entries.

#### Acceptance Criteria

1. THE Finance_System SHALL provide a search input on the invoices list that filters by student name, invoice number, and fee category.
2. THE Finance_System SHALL provide filter controls on the invoices list for class, session, status (paid/partial/unpaid), and due date range.
3. THE Finance_System SHALL provide a search input on the payments list that filters by student name and receipt number.
4. THE Finance_System SHALL provide filter controls on the payments list for payment method, date range, and fee category.
5. THE Finance_System SHALL provide a search input on the expenses list that filters by title and description.
6. THE Finance_System SHALL provide filter controls on the expenses list for expense category, date range, and recurring status.
7. THE Finance_System SHALL provide filter controls on the payroll list for month, year, and department.
8. WHEN a search or filter is applied, THE Finance_System SHALL preserve the active filter state in the URL query string so the page can be bookmarked and shared.
9. WHEN no results match the applied filters, THE Finance_System SHALL display a clear "No records found" message.

---

### Requirement 22: Pagination

**User Story:** As an Admin or Accountant, I want all list pages to be paginated, so that large datasets do not degrade page performance or usability.

#### Acceptance Criteria

1. THE Finance_System SHALL paginate all list views (invoices, payments, expenses, payroll, audit logs) with a default page size of 20 records per page.
2. THE Finance_System SHALL display pagination controls (previous, page numbers, next) below each list.
3. WHEN a filter or search is active, THE Finance_System SHALL preserve the filter parameters across page navigation.
4. THE Finance_System SHALL display the total record count and current page range (e.g. "Showing 1–20 of 143") above or below each paginated list.

---

### Requirement 23: Export Reports (PDF and CSV)

**User Story:** As an Admin or Accountant, I want to export any financial report or list as a PDF or CSV file, so that I can share and archive financial data offline.

#### Acceptance Criteria

1. THE Report_Engine SHALL provide a PDF export for each report: income, expense, profit/loss, outstanding fees, and salary reports.
2. THE Report_Engine SHALL provide a CSV export for each report: income, expense, profit/loss, outstanding fees, and salary reports.
3. THE Report_Engine SHALL provide a CSV export for the invoices list, respecting any active search and filter parameters.
4. THE Report_Engine SHALL provide a CSV export for the payments list, respecting any active search and filter parameters.
5. THE Report_Engine SHALL provide a CSV export for the expenses list, respecting any active search and filter parameters.
6. WHEN a PDF is exported, THE Report_Engine SHALL include the school name, report title, date range, and generation timestamp in the PDF header.
7. WHEN a CSV is exported, THE Report_Engine SHALL include a header row with column names matching the on-screen table columns.
8. THE Report_Engine SHALL name exported files descriptively (e.g. `invoices_2024-2025_2026-05-06.csv`).

---

### Requirement 24: Responsive Design

**User Story:** As any user, I want the finance module pages to be usable on mobile and tablet devices, so that I can access financial information from any device.

#### Acceptance Criteria

1. THE Finance_System SHALL use Bootstrap 4 responsive grid classes (`col-*`, `col-md-*`, `col-lg-*`) exclusively for layout — no custom CSS frameworks or Tailwind — so that all pages reflow correctly on screen widths from 320 px (mobile) to 1920 px (desktop).
2. THE Finance_System SHALL wrap all data tables in Bootstrap's `table-responsive` div so that tables scroll horizontally on small screens without clipping content.
3. THE Finance_System SHALL use Bootstrap `form-row` and `col-*` classes to stack filter form controls into a single column on screens narrower than the `md` breakpoint (768 px).
4. THE Finance_System SHALL use Bootstrap `btn` sizing classes (`btn-sm`, `btn-lg`) and standard Bootstrap form control classes (`form-control`) to ensure consistent touch-friendly sizing across all devices.
5. THE Finance_System SHALL use Bootstrap responsive display utilities (`d-none d-md-table-cell`) to hide non-essential table columns on mobile viewports, keeping tables readable without horizontal overflow.
6. THE Finance_System SHALL use Bootstrap card components (`card`, `card-body`, `card-header`) for all content panels so that layout adapts consistently across breakpoints.
7. THE Finance_System SHALL include a `@media print` block in receipt and invoice blade views that hides the sidebar, top navigation, and filter controls, using only Bootstrap and inline styles — no external CSS files.
