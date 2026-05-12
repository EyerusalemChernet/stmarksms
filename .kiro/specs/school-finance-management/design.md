# Design Document: School Finance Management

## Overview

This design extends the existing Laravel 8 SMS application with a comprehensive finance module. The existing partial fee management (FeeCategory, FeeStructure, StudentFeeInvoice, FeePayment) is retained and extended. New modules add payroll, expense management, a finance dashboard, role-based access for an Accountant role, in-app notifications, audit logging hooks, and full PDF/CSV export.

All monetary values are in ETB (Ethiopian Birr). The UI is Bootstrap 4 with Bootstrap Icons. No new frontend frameworks are introduced.

### Key Design Decisions

- **Extend, don't replace**: `StudentFeeController` is refactored to add missing guards (duplicate structure check, delete guards) and search/pagination. No existing routes are removed.
- **`accountant` middleware**: Follows the same pattern as `HRManager` middleware — checks `user_type === 'accountant'`. Finance routes are re-gated to allow both `hr_manager` and `accountant`.
- **`FinanceMiddleware`**: A single new middleware that permits `admin`, `super_admin`, `hr_manager`, and `accountant` — used on all finance management routes.
- **Service classes**: `FinanceNotificationService` and `AuditService` are thin wrappers to keep controllers clean. No heavy service layer otherwise.
- **PDF generation**: Uses the already-installed `barryvdh/laravel-dompdf` package, consistent with `ReportController`.
- **CSV export**: Streamed via Laravel's `StreamedResponse` — no extra packages needed.
- **Charts**: Chart.js (CDN) — lightweight, Bootstrap 4 compatible, no build step required.
- **AuditLog**: The existing `AuditLog` model is extended with `before_values` and `after_values` JSON columns via a new migration.


---

## Architecture

```
routes/web.php
  └── Finance group (middleware: finance_access)
        ├── FinanceDashboardController
        ├── StudentFeeController          (extended)
        ├── PayrollController             (new)
        ├── ExpenseController             (new)
        └── FinanceReportController       (new)

app/Http/Middleware/Custom/
  └── Accountant.php                      (new)
  └── FinanceAccess.php                   (new — allows hr_manager|accountant|admin|super_admin)

app/Models/
  ├── SalaryStructure.php                 (new)
  ├── Payroll.php                         (new)
  ├── ExpenseCategory.php                 (new)
  └── Expense.php                         (new)

app/Services/
  ├── AuditService.php                    (new)
  └── FinanceNotificationService.php      (new)

resources/views/pages/finance/
  ├── dashboard.blade.php                 (new)
  ├── fees/                               (existing, extended)
  ├── payroll/
  │   ├── index.blade.php
  │   ├── create.blade.php
  │   ├── show.blade.php
  │   └── payslip_pdf.blade.php
  ├── expenses/
  │   ├── index.blade.php
  │   ├── create.blade.php
  │   └── edit.blade.php
  ├── expense_categories/
  │   └── index.blade.php
  └── reports/
      ├── index.blade.php
      ├── income.blade.php
      ├── expense_report.blade.php
      ├── profit_loss.blade.php
      ├── outstanding.blade.php
      ├── salary_report.blade.php
      └── pdf/                            (PDF-only blade variants)
```

The existing `fees.*` routes are re-gated from `hr_manager` to `finance_access` middleware. The `hr_manager` middleware remains for HR-specific routes (departments, staff attendance, workload).


---

## Components and Interfaces

### Middleware

**`App\Http\Middleware\Custom\Accountant`**
- Checks `Auth::user()->user_type === 'accountant'`
- Redirects to `dashboard` with `flash_danger` on failure
- Registered in `Kernel::$routeMiddleware` as `'accountant'`

**`App\Http\Middleware\Custom\FinanceAccess`**
- Permits `user_type` in `['admin', 'super_admin', 'hr_manager', 'accountant']`
- Redirects to `dashboard` with `flash_danger` on failure
- Registered as `'finance_access'`

### Controllers

**`App\Http\Controllers\Finance\FinanceDashboardController`**
```
index()   → finance.dashboard   (GET /finance/dashboard)
```

**`App\Http\Controllers\Finance\StudentFeeController`** (extended)
- Existing methods retained; the following are added or modified:
```
invoicePdf($id)          → GET /fees/invoice/{id}/pdf
receiptPdf($id)          → GET /fees/receipt/{id}/pdf
refund(Request, $id)     → POST /fees/refund/{id}
invoicesCsv(Request)     → GET /fees/invoices/export/csv
paymentsCsv(Request)     → GET /fees/payments/export/csv
sendReminder($id)        → POST /fees/invoice/{id}/remind
```
- `invoices()` and `payments()` gain search/filter/pagination (20 per page)
- `destroyCategory()` gains guard: reject if structures exist
- `destroyStructure()` gains guard: reject if invoices exist
- `storeStructure()` gains duplicate check

**`App\Http\Controllers\Finance\PayrollController`**
```
index(Request)           → GET  /finance/payroll
create()                 → GET  /finance/payroll/create
store(Request)           → POST /finance/payroll
show($id)                → GET  /finance/payroll/{id}
void($id)                → POST /finance/payroll/{id}/void
payslipPdf($id)          → GET  /finance/payroll/{id}/payslip
salaryStructures()       → GET  /finance/salary-structures
storeSalaryStructure(Request)  → POST /finance/salary-structures
updateSalaryStructure(Request, $id) → PUT /finance/salary-structures/{id}
```

**`App\Http\Controllers\Finance\ExpenseController`**
```
index(Request)           → GET    /finance/expenses
create()                 → GET    /finance/expenses/create
store(Request)           → POST   /finance/expenses
edit($id)                → GET    /finance/expenses/{id}/edit
update(Request, $id)     → PUT    /finance/expenses/{id}
destroy($id)             → DELETE /finance/expenses/{id}
exportCsv(Request)       → GET    /finance/expenses/export/csv
categories()             → GET    /finance/expense-categories
storeCategory(Request)   → POST   /finance/expense-categories
updateCategory(Request, $id) → PUT /finance/expense-categories/{id}
destroyCategory($id)     → DELETE /finance/expense-categories/{id}
```

**`App\Http\Controllers\Finance\FinanceReportController`**
```
index()                  → GET /finance/reports
income(Request)          → GET /finance/reports/income
expenses(Request)        → GET /finance/reports/expenses
profitLoss(Request)      → GET /finance/reports/profit-loss
outstanding(Request)     → GET /finance/reports/outstanding
salary(Request)          → GET /finance/reports/salary
exportPdf(Request, $type) → GET /finance/reports/{type}/pdf
exportCsv(Request, $type) → GET /finance/reports/{type}/csv
```

### Services

**`App\Services\AuditService`**
```php
public static function log(string $action, string $module, int $recordId, array $before = [], array $after = []): void
```
Wraps `AuditLog::create()`, populating `before_values` and `after_values` as JSON. Called from controller methods after each create/update/delete on financial models.

**`App\Services\FinanceNotificationService`**
```php
public static function sendFeeReminder(StudentFeeInvoice $invoice): void
public static function sendPaymentConfirmation(FeePayment $payment): void
public static function sendSalaryNotification(Payroll $payroll): void
```
Each method creates `Message` records using the existing `Message` model (`sender_id` = system admin user, `receiver_id` = target user). `sendFeeReminder` also updates `invoice->reminder_sent_at`.


---

## Data Models

### New Tables

**`salary_structures`**
```
id                  bigint unsigned PK
user_id             bigint unsigned FK → users.id
basic_salary        decimal(10,2)
housing_allowance   decimal(10,2)
transport_allowance decimal(10,2)
other_allowances    decimal(10,2)
income_tax_pct      decimal(5,2)
loan_repayment      decimal(10,2)
absence_deduction_rate decimal(10,2)
active              boolean default true
created_at, updated_at
```
- One active structure per staff member (enforced by unique index on `user_id` where `active = true`)
- Relationships: `belongsTo(User::class, 'user_id')`

**`payrolls`**
```
id                  bigint unsigned PK
user_id             bigint unsigned FK → users.id
month               tinyint (1-12)
year                smallint
basic_salary        decimal(10,2)
housing_allowance   decimal(10,2)
transport_allowance decimal(10,2)
other_allowances    decimal(10,2)
bonus               decimal(10,2) default 0
gross_salary        decimal(10,2)
income_tax          decimal(10,2)
loan_repayment      decimal(10,2)
absence_deduction   decimal(10,2)
total_deductions    decimal(10,2)
net_salary          decimal(10,2)
absence_days        tinyint default 0
voided              boolean default false
processed_at        timestamp
created_at, updated_at
```
- Unique index on `(user_id, month, year)` where `voided = false`
- Relationships: `belongsTo(User::class, 'user_id')`

**`expense_categories`**
```
id          bigint unsigned PK
name        varchar(100) unique
description text nullable
created_at, updated_at
```
- Relationships: `hasMany(Expense::class, 'expense_category_id')`

**`expenses`**
```
id                  bigint unsigned PK
expense_category_id bigint unsigned FK → expense_categories.id
title               varchar(200)
amount              decimal(10,2)
expense_date        date
description         text nullable
receipt_file        varchar(255) nullable
recurring           boolean default false
recurrence_interval enum('monthly','quarterly','annually') nullable
created_at, updated_at
```
- Relationships: `belongsTo(ExpenseCategory::class, 'expense_category_id')`

### Extended Tables

**`audit_logs`** (add columns via migration)
```
before_values  json nullable
after_values   json nullable
```
- Existing columns: `id`, `user_id`, `action`, `module`, `description`, `ip_address`, `created_at`, `updated_at`

**`student_fee_invoices`** (add column via migration)
```
reminder_sent_at  timestamp nullable
```

### Model Relationships

**`User`**
- `hasOne(SalaryStructure::class, 'user_id')->where('active', true)`
- `hasMany(Payroll::class, 'user_id')`

**`SalaryStructure`**
- `belongsTo(User::class, 'user_id')`

**`Payroll`**
- `belongsTo(User::class, 'user_id')`

**`ExpenseCategory`**
- `hasMany(Expense::class, 'expense_category_id')`

**`Expense`**
- `belongsTo(ExpenseCategory::class, 'expense_category_id')`

**`StudentFeeInvoice`** (existing, no new relationships)

**`FeePayment`** (existing, no new relationships)


### Route Structure

All new finance routes live under the `Finance` namespace with `finance_access` middleware. The existing `fees.*` route group is re-gated from `hr_manager` to `finance_access`.

```php
// Finance namespace, finance_access middleware
Route::group(['namespace' => 'Finance', 'middleware' => 'finance_access', 'prefix' => 'finance'], function () {

    // Dashboard
    Route::get('/dashboard', 'FinanceDashboardController@index')->name('finance.dashboard');

    // Payroll
    Route::get('/payroll', 'PayrollController@index')->name('payroll.index');
    Route::get('/payroll/create', 'PayrollController@create')->name('payroll.create');
    Route::post('/payroll', 'PayrollController@store')->name('payroll.store');
    Route::get('/payroll/{id}', 'PayrollController@show')->name('payroll.show');
    Route::post('/payroll/{id}/void', 'PayrollController@void')->name('payroll.void');
    Route::get('/payroll/{id}/payslip', 'PayrollController@payslipPdf')->name('payroll.payslip');

    // Salary Structures
    Route::get('/salary-structures', 'PayrollController@salaryStructures')->name('salary.index');
    Route::post('/salary-structures', 'PayrollController@storeSalaryStructure')->name('salary.store');
    Route::put('/salary-structures/{id}', 'PayrollController@updateSalaryStructure')->name('salary.update');

    // Expenses
    Route::get('/expenses', 'ExpenseController@index')->name('expenses.index');
    Route::get('/expenses/create', 'ExpenseController@create')->name('expenses.create');
    Route::post('/expenses', 'ExpenseController@store')->name('expenses.store');
    Route::get('/expenses/{id}/edit', 'ExpenseController@edit')->name('expenses.edit');
    Route::put('/expenses/{id}', 'ExpenseController@update')->name('expenses.update');
    Route::delete('/expenses/{id}', 'ExpenseController@destroy')->name('expenses.destroy');
    Route::get('/expenses/export/csv', 'ExpenseController@exportCsv')->name('expenses.csv');

    // Expense Categories
    Route::get('/expense-categories', 'ExpenseController@categories')->name('expense_cats.index');
    Route::post('/expense-categories', 'ExpenseController@storeCategory')->name('expense_cats.store');
    Route::put('/expense-categories/{id}', 'ExpenseController@updateCategory')->name('expense_cats.update');
    Route::delete('/expense-categories/{id}', 'ExpenseController@destroyCategory')->name('expense_cats.destroy');

    // Reports
    Route::get('/reports', 'FinanceReportController@index')->name('finance.reports');
    Route::get('/reports/income', 'FinanceReportController@income')->name('reports.income');
    Route::get('/reports/expenses', 'FinanceReportController@expenses')->name('reports.expenses');
    Route::get('/reports/profit-loss', 'FinanceReportController@profitLoss')->name('reports.profit_loss');
    Route::get('/reports/outstanding', 'FinanceReportController@outstanding')->name('reports.outstanding');
    Route::get('/reports/salary', 'FinanceReportController@salary')->name('reports.salary');
    Route::get('/reports/{type}/pdf', 'FinanceReportController@exportPdf')->name('reports.pdf');
    Route::get('/reports/{type}/csv', 'FinanceReportController@exportCsv')->name('reports.csv');
});

// Fees routes re-gated to finance_access (prefix: fees)
Route::group(['namespace' => 'Finance', 'middleware' => 'finance_access', 'prefix' => 'fees'], function () {
    // ... all existing fees.* routes unchanged ...
    // Additional:
    Route::get('/invoice/{id}/pdf', 'StudentFeeController@invoicePdf')->name('fees.invoice.pdf');
    Route::get('/receipt/{id}/pdf', 'StudentFeeController@receiptPdf')->name('fees.receipt.pdf');
    Route::post('/refund/{id}', 'StudentFeeController@refund')->name('fees.refund');
    Route::get('/invoices/export/csv', 'StudentFeeController@invoicesCsv')->name('fees.invoices.csv');
    Route::get('/payments/export/csv', 'StudentFeeController@paymentsCsv')->name('fees.payments.csv');
    Route::post('/invoice/{id}/remind', 'StudentFeeController@sendReminder')->name('fees.remind');
});

// Student/Parent: own invoices only
Route::group(['namespace' => 'Finance', 'middleware' => 'auth', 'prefix' => 'my-fees'], function () {
    Route::get('/', 'StudentFeeController@myInvoices')->name('my_fees.index');
    Route::get('/{id}', 'StudentFeeController@myInvoiceDetail')->name('my_fees.show');
    Route::post('/chapa/{id}', 'StudentFeeController@initiateChapa')->name('my_fees.chapa');
});
```


### Blade View Structure

All views extend the existing app layout (`layouts.app` or equivalent). Bootstrap 4 grid, `table-responsive`, `card`, and `form-row` classes are used throughout.

```
resources/views/pages/finance/
├── dashboard.blade.php
│     Cards: total fees collected, outstanding balance, expenses, payroll cost
│     Chart.js bar chart (income vs expense, current year)
│     Chart.js pie chart (invoice status breakdown)
│
├── fees/                          (existing views, extended)
│   ├── categories.blade.php       add structure count column
│   ├── structures.blade.php       add duplicate guard feedback
│   ├── invoices.blade.php         add search bar, filters, pagination, CSV export btn
│   ├── invoice_detail.blade.php   add refund form, PDF download btn, remind btn
│   ├── payments.blade.php         add search bar, filters, pagination, CSV export btn
│   ├── receipt.blade.php          add @media print block
│   ├── invoice_pdf.blade.php      (new) DomPDF template
│   ├── receipt_pdf.blade.php      (new) DomPDF template
│   ├── pending.blade.php          add pagination
│   └── report.blade.php           (replaced by FinanceReportController)
│
├── payroll/
│   ├── index.blade.php            list with month/year/dept filters, pagination
│   ├── create.blade.php           staff selector, bonus/absence fields
│   ├── show.blade.php             payroll detail, void button (admin only)
│   └── payslip_pdf.blade.php      DomPDF template
│
├── salary_structures/
│   └── index.blade.php            list + inline create/edit forms per staff
│
├── expenses/
│   ├── index.blade.php            list with category/date/recurring filters, pagination, CSV btn
│   ├── create.blade.php           form with file upload
│   └── edit.blade.php
│
├── expense_categories/
│   └── index.blade.php            list + inline create/edit/delete
│
└── reports/
    ├── index.blade.php            report type selector
    ├── income.blade.php
    ├── expense_report.blade.php
    ├── profit_loss.blade.php
    ├── outstanding.blade.php
    ├── salary_report.blade.php
    └── pdf/
        ├── income_pdf.blade.php
        ├── expense_pdf.blade.php
        ├── profit_loss_pdf.blade.php
        ├── outstanding_pdf.blade.php
        └── salary_pdf.blade.php
```

**Search/Filter Pattern** (consistent across all list views):
- Filter form uses `GET` method so parameters appear in URL query string
- `request()->query()` is merged into pagination links via `->appends(request()->query())`
- "No records found" empty state shown when `$records->isEmpty()`
- Record count shown as "Showing X–Y of Z" using `$records->firstItem()`, `$records->lastItem()`, `$records->total()`

**PDF Blade Templates**:
- Inline CSS only (no external stylesheets — DomPDF limitation)
- Header includes school name (`Qs::getSystemName()`), report title, date range, generation timestamp
- All amounts formatted as `ETB {{ number_format($amount, 2) }}`

**Print CSS** (in receipt and invoice views):
```html
<style>
  @media print {
    .sidebar, .navbar, .filter-panel, .btn { display: none !important; }
  }
</style>
```


---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Fee category delete guard

*For any* fee category that has one or more associated fee structures, attempting to delete it must be rejected and the category must still exist afterward.

**Validates: Requirements 1.3, 1.4**

---

### Property 2: Fee structure uniqueness

*For any* existing fee structure with a given (fee_category_id, my_class_id, session) combination, submitting a second create request with the same combination must be rejected and only one structure must exist for that combination.

**Validates: Requirements 2.3, 2.4**

---

### Property 3: Invoice creation invariants

*For any* newly created invoice, the following must all hold simultaneously: `status = 'unpaid'`, `balance = fee_structure.amount`, `amount_paid = 0`, and `due_date = created_at + 30 days`.

**Validates: Requirements 3.3, 3.4**

---

### Property 4: Class fee assignment completeness

*For any* class with N active non-graduated students who do not already have an invoice for a given fee structure and session, assigning that fee structure to the class must create exactly N new invoices.

**Validates: Requirements 3.1, 3.2**

---

### Property 5: Net amount invariant

*For any* invoice after any combination of discount and fine applications, `net_amount = original_amount - discount + fine` must hold, and `net_amount >= 0` must always hold.

**Validates: Requirements 4.1, 4.2, 4.3**

---

### Property 6: Payment status synchronisation

*For any* invoice after any sequence of payment recordings, the following invariants must hold simultaneously: `amount_paid = SUM(payments.amount)`, `balance = max(0, net_amount - amount_paid)`, `status = 'paid'` iff `balance = 0`, `status = 'partial'` iff `balance > 0 AND amount_paid > 0`, `status = 'unpaid'` iff `amount_paid = 0`.

**Validates: Requirements 4.4, 5.3**

---

### Property 7: Payment amount ceiling

*For any* invoice with a given balance, recording a payment with `amount > balance` must be rejected and the invoice balance must remain unchanged.

**Validates: Requirements 5.2**

---

### Property 8: Receipt number uniqueness

*For any* set of recorded payments, all `receipt_no` values must be distinct across the entire `fee_payments` table.

**Validates: Requirements 5.4**

---

### Property 9: Chapa failed callback does not alter invoice balance

*For any* invoice, receiving a Chapa callback with status `'failed'` or `'pending'` must leave the invoice `balance` and `status` unchanged.

**Validates: Requirements 6.3, 6.4**

---

### Property 10: Refund ceiling

*For any* invoice, attempting a refund with `amount > total_amount_paid` must be rejected and the invoice balance must remain unchanged.

**Validates: Requirements 7.1, 7.2**

---

### Property 11: Invoice and receipt PDF field completeness

*For any* invoice, the generated invoice PDF string must contain the student name, class, session, fee category, original amount, discount, fine, net amount, amount paid, balance, status, and due date. *For any* payment, the generated receipt PDF string must contain the receipt number, student name, payment amount, payment method, transaction reference, payment date, and collector name.

**Validates: Requirements 8.1, 8.2**

---

### Property 12: Payroll calculation correctness

*For any* payroll record, the following arithmetic invariants must hold: `gross_salary = basic_salary + housing_allowance + transport_allowance + other_allowances + bonus`, `total_deductions = (income_tax_pct / 100 * gross_salary) + loan_repayment + (absence_days * absence_deduction_rate)`, `net_salary = gross_salary - total_deductions`.

**Validates: Requirements 10.1, 10.2, 10.3**

---

### Property 13: Duplicate payroll prevention

*For any* existing non-voided payroll record for a given (user_id, month, year), attempting to create another payroll for the same combination must be rejected.

**Validates: Requirements 10.4**

---

### Property 14: Payslip PDF field completeness

*For any* payroll record, the generated payslip PDF string must contain the staff name, position, department, month, year, gross salary, each allowance itemised, each deduction itemised, and net salary, all with the ETB currency symbol.

**Validates: Requirements 11.1, 11.2**

---

### Property 15: Expense category delete guard

*For any* expense category that has one or more associated expenses, attempting to delete it must be rejected and the category must still exist afterward.

**Validates: Requirements 13.3**

---

### Property 16: Expense receipt file validation

*For any* file upload for an expense receipt, files with MIME type other than `application/pdf`, `image/jpeg`, or `image/png`, or with size exceeding 5 MB, must be rejected.

**Validates: Requirements 12.6**

---

### Property 17: Report aggregation correctness

*For any* date range, the income report total must equal the sum of all `fee_payments.amount` within that range; the expense report total must equal the sum of all `expenses.amount` within that range; and the profit/loss figure must equal income total minus expense total.

**Validates: Requirements 14.1, 14.2, 14.3**

---

### Property 18: Outstanding fees report completeness

*For any* set of invoices, the outstanding fees report must contain exactly those invoices whose `status` is `'unpaid'` or `'partial'`, and must not contain any invoice with `status = 'paid'`.

**Validates: Requirements 14.4**

---

### Property 19: Dashboard data scoping

*For any* dashboard data load, the "total fees collected this month" figure must equal the sum of `fee_payments.amount` where `paid_at` falls within the current calendar month, and the "total expenses this month" figure must equal the sum of `expenses.amount` where `expense_date` falls within the current calendar month.

**Validates: Requirements 15.1, 15.4**

---

### Property 20: Role-based access enforcement

*For any* user whose `user_type` is not in `['admin', 'super_admin', 'hr_manager', 'accountant']`, any request to a finance management route must result in a redirect to the dashboard with an unauthorised error message. *For any* user whose `user_type` is `'student'` or `'my_parent'`, requests to finance routes for other students' invoices must be rejected.

**Validates: Requirements 16.1, 16.2, 16.3, 16.5**

---

### Property 21: Fee reminder notification and timestamp

*For any* invoice whose `due_date` has passed and whose `status` is `'unpaid'` or `'partial'`, after a reminder is dispatched, a `Message` record must exist for the associated student (and parent if linked), and `invoice.reminder_sent_at` must be set to a non-null timestamp.

**Validates: Requirements 17.1, 17.2, 17.3**

---

### Property 22: Payment confirmation notification

*For any* successfully recorded `FeePayment`, a `Message` record must exist for the associated student containing the receipt number, amount paid, and remaining balance.

**Validates: Requirements 18.1**

---

### Property 23: Salary notification on payroll creation

*For any* created `Payroll` record, a `Message` record must exist for the associated staff member containing the net salary amount and the month/year.

**Validates: Requirements 19.1**

---

### Property 24: Audit log completeness

*For any* create, update, or delete operation on a financial model (StudentFeeInvoice, FeePayment, Payroll, Expense, SalaryStructure), an `AuditLog` record must exist with the correct `action`, `module`, `user_id`, and timestamp. For update operations, `before_values` and `after_values` must both be non-null and reflect the changed fields.

**Validates: Requirements 20.1, 20.2**

---

### Property 25: Filter correctness

*For any* list view (invoices, payments, expenses, payroll) with any combination of active filter parameters, every record in the returned result set must satisfy all active filter conditions, and no record satisfying all conditions must be absent from the result set.

**Validates: Requirements 21.1–21.9**

---

### Property 26: Pagination invariant

*For any* paginated list with N total records and page size P, page K must contain exactly `min(P, N - (K-1)*P)` records, and the union of all pages must equal the full unfiltered (or filtered) result set with no duplicates.

**Validates: Requirements 22.1–22.4**

---

### Property 27: CSV export completeness

*For any* CSV export of a list or report with active filters, the exported rows must correspond exactly to the filtered result set, the first row must be a header row with column names matching the on-screen table, and the filename must follow the descriptive naming convention.

**Validates: Requirements 23.2–23.8**


---

## Error Handling

### Validation Errors
All controller methods use Laravel's `$request->validate()`. Validation failures return back with `$errors` bag, displayed via the existing `@include('partials.errors')` pattern used throughout the app.

### Guard Rejections (business rules)
- Delete guards (fee category with structures, expense category with expenses) return `back()->with('flash_danger', '...')`.
- Duplicate fee structure: `back()->with('flash_danger', 'A fee structure for this category, class, and session already exists.')`.
- Payment exceeding balance: validation rule `max:{$invoice->balance}` on the amount field.
- Refund exceeding total paid: validation rule `max:{$invoice->amount_paid}`.
- Duplicate payroll: checked before insert, returns `back()->with('flash_danger', 'Payroll for this staff member, month, and year already exists.')`.

### File Upload Errors
Receipt file uploads validated with `mimes:pdf,jpg,jpeg,png|max:5120`. Failures return standard Laravel validation errors.

### Chapa Callback Errors
Failed/pending callbacks are logged via `AuditService::log('chapa_callback', 'Finance', $invoiceId, [], ['status' => $status])` and return HTTP 200 to Chapa (to prevent retries) without modifying the invoice.

### Unauthorised Access
`FinanceAccess` and `Accountant` middleware redirect to `route('dashboard')` with `flash_danger` message. Student/Parent accessing another student's invoice returns `abort(403)`.

### PDF Generation Errors
DomPDF errors are caught with a try/catch; on failure the user is redirected back with `flash_danger`. No raw exceptions are exposed.

---

## Testing Strategy

### Dual Testing Approach

Both unit tests and property-based tests are required. Unit tests cover specific examples, integration points, and edge cases. Property-based tests verify universal correctness across randomised inputs.

### Unit Tests (PHPUnit)

Located in `tests/Feature/Finance/`. One test class per controller:

- `FeeCategoryTest` — CRUD, delete guard, structure count
- `FeeStructureTest` — duplicate prevention, delete guard
- `InvoiceTest` — assignment, discount/fine, refund, Chapa callback handling
- `PaymentTest` — recording, receipt uniqueness, status sync
- `PayrollTest` — calculation, duplicate prevention, void
- `ExpenseTest` — CRUD, file upload validation, category delete guard
- `ReportTest` — aggregation totals, outstanding filter, PDF/CSV output
- `DashboardTest` — summary figures scoped to current month/session
- `AccessControlTest` — middleware enforcement per role
- `NotificationTest` — message creation on payment, payroll, reminder
- `AuditLogTest` — log entry creation with before/after values

### Property-Based Tests (Pest + `spatie/pest-plugin-test-time` or raw PHPUnit data providers)

Since the project is PHP/Laravel, property-based testing is implemented using **[Eris](https://github.com/giorgiosironi/eris)** (PHP property-based testing library) or, if unavailable, using PHPUnit `@dataProvider` with randomised data generators in a `tests/Property/Finance/` directory.

Each property test runs a minimum of **100 iterations**.

Each test is tagged with a comment in the format:
`// Feature: school-finance-management, Property N: <property_text>`

**Property test mapping:**

| Property | Test class | Pattern |
|---|---|---|
| 1 — Fee category delete guard | `FeeCategoryPropertyTest` | Error condition |
| 2 — Fee structure uniqueness | `FeeStructurePropertyTest` | Error condition |
| 3 — Invoice creation invariants | `InvoicePropertyTest` | Invariant |
| 4 — Class assignment completeness | `InvoicePropertyTest` | Invariant |
| 5 — Net amount invariant | `InvoicePropertyTest` | Invariant |
| 6 — Payment status sync | `PaymentPropertyTest` | Invariant |
| 7 — Payment ceiling | `PaymentPropertyTest` | Error condition |
| 8 — Receipt uniqueness | `PaymentPropertyTest` | Invariant |
| 9 — Chapa failed callback | `ChapaPropertyTest` | Invariant |
| 10 — Refund ceiling | `InvoicePropertyTest` | Error condition |
| 11 — PDF field completeness | `PdfPropertyTest` | Invariant |
| 12 — Payroll calculation | `PayrollPropertyTest` | Invariant (arithmetic) |
| 13 — Duplicate payroll | `PayrollPropertyTest` | Error condition |
| 14 — Payslip PDF fields | `PdfPropertyTest` | Invariant |
| 15 — Expense category delete guard | `ExpenseCategoryPropertyTest` | Error condition |
| 16 — File upload validation | `ExpensePropertyTest` | Error condition |
| 17 — Report aggregation | `ReportPropertyTest` | Model-based (sum vs query) |
| 18 — Outstanding report | `ReportPropertyTest` | Invariant |
| 19 — Dashboard scoping | `DashboardPropertyTest` | Invariant |
| 20 — Role access | `AccessControlPropertyTest` | Error condition |
| 21 — Reminder notification | `NotificationPropertyTest` | Round-trip |
| 22 — Payment confirmation | `NotificationPropertyTest` | Round-trip |
| 23 — Salary notification | `NotificationPropertyTest` | Round-trip |
| 24 — Audit log completeness | `AuditPropertyTest` | Invariant |
| 25 — Filter correctness | `FilterPropertyTest` | Metamorphic |
| 26 — Pagination invariant | `PaginationPropertyTest` | Invariant |
| 27 — CSV export completeness | `ExportPropertyTest` | Round-trip |

### Test Configuration

```php
// Example property test structure
// Feature: school-finance-management, Property 12: Payroll calculation correctness
public function test_payroll_calculation_invariant(): void
{
    for ($i = 0; $i < 100; $i++) {
        $basic    = rand(1000, 50000) / 100;
        $housing  = rand(0, 10000) / 100;
        $transport = rand(0, 5000) / 100;
        $other    = rand(0, 5000) / 100;
        $bonus    = rand(0, 5000) / 100;
        $taxPct   = rand(0, 35);
        $loan     = rand(0, 2000) / 100;
        $absenceDays = rand(0, 26);
        $absenceRate = rand(0, 500) / 100;

        $gross = $basic + $housing + $transport + $other + $bonus;
        $deductions = ($taxPct / 100 * $gross) + $loan + ($absenceDays * $absenceRate);
        $net = $gross - $deductions;

        $payroll = Payroll::factory()->create([...]);

        $this->assertEquals(round($gross, 2), round($payroll->gross_salary, 2));
        $this->assertEquals(round($deductions, 2), round($payroll->total_deductions, 2));
        $this->assertEquals(round($net, 2), round($payroll->net_salary, 2));
    }
}
```

