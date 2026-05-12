# St. Mark School ERP — System Architecture

> **St. Mark Primary School, Addis Ababa, Ethiopia**
> Version 1.0 | Laravel 8 | Generated from codebase analysis

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [System Architecture](#2-system-architecture)
3. [Functional Modules](#3-functional-modules)
4. [Algorithms and Logic](#4-algorithms-and-logic)
5. [Database Design](#5-database-design)
6. [API and Routing](#6-api-and-routing)
7. [User Roles and Permissions](#7-user-roles-and-permissions)
8. [UI/UX Structure](#8-uiux-structure)
9. [System Workflows](#9-system-workflows)
10. [Security and Validation](#10-security-and-validation)
11. [Assumptions and Limitations](#11-assumptions-and-limitations)
12. [Suggestions for Improvement](#12-suggestions-for-improvement)

---

# 1. System Overview

## 1.1 Project Name and Purpose

**St. Mark School Management System (SMS)** is a full-featured, web-based Enterprise Resource Planning (ERP) system built specifically for St. Mark Primary School in Addis Ababa, Ethiopia. It digitises and centralises every administrative, academic, and HR operation of the school into a single platform.

## 1.2 Problem It Solves

Before this system, the school relied on paper-based records, spreadsheets, and disconnected tools for:
- Student admissions and record-keeping
- Exam marks entry and report card generation
- Staff payroll and attendance tracking
- Leave management and HR operations
- Fee collection and financial reporting

This system replaces all of those with a unified, role-controlled web application accessible from any browser.

## 1.3 Target Users

| Role | Description |
|------|-------------|
| `super_admin` | Full system access. Manages settings, users, and all modules. |
| `admin` | Same as super_admin except cannot access System Settings. |
| `teacher` | Marks attendance for their homeroom class, enters exam marks, views timetables. |
| `hr_manager` | Exclusive access to HR, payroll, leave, recruitment, performance, and finance modules. |
| `parent` | Read-only portal to view their child's academic records and attendance. |
| `student` | Cannot log in directly. Managed through the parent portal. |

## 1.4 Key Features and Modules

- **Student Management** — Admission (single + bulk CSV), profiles, promotion, graduation
- **Academics** — Classes, sections, subjects, exams, marks entry, grading, marksheets, tabulation
- **Timetable** — Create and manage class/exam timetables with conflict detection
- **Attendance** — Homeroom teacher marks student attendance; dropout early warning system
- **HR Management** — Employee profiles, departments, positions, shifts, salary history
- **Payroll** — Monthly payroll generation with Ethiopian tax/pension calculations
- **Leave Management** — Policy configuration, request/approval workflow, balance tracking
- **Recruitment** — Job postings, application pipeline (applied → shortlisted → hired)
- **Performance Reviews** — Weighted category scoring with overall score calculation
- **Finance** — Fee categories, structures, student invoices, payment collection
- **Library** — Book inventory, borrow/return requests, history
- **Communication** — Internal messaging, announcements, AI message summarisation
- **AI Features** — Report card comment generation, attendance risk prediction, grade analysis
- **Amharic/English** — Full bilingual UI toggle (477 translation pairs)
- **Audit Logs** — Every create/update/delete action is logged with user and timestamp

---

# 2. System Architecture

## 2.1 Overall Architecture

The system follows the **MVC (Model-View-Controller)** pattern as implemented by Laravel 8. The request lifecycle is:

```
Browser Request
    → routes/web.php (route matching)
    → Middleware stack (auth, role check)
    → Controller method
    → Service layer (business logic)
    → Repository / Model (data access)
    → Blade View (HTML response)
    → Browser
```

There is no REST API — all communication is server-rendered HTML with occasional AJAX JSON responses for modals and dynamic dropdowns.

## 2.2 Technology Stack

### Backend
| Component | Technology |
|-----------|-----------|
| Framework | Laravel 8 |
| Language | PHP 8.3 |
| Auth | Laravel built-in (`AuthenticatesUsers` trait) |
| ORM | Eloquent |
| PDF Export | barryvdh/laravel-dompdf |
| AI Integration | Ollama (TinyLlama model) via Guzzle HTTP |
| ID Hashing | hashids/hashids |

### Frontend
| Component | Technology |
|-----------|-----------|
| Templating | Laravel Blade |
| CSS Framework | Bootstrap 5 + custom `modern.css` |
| Icons | Bootstrap Icons (bi-*) |
| JavaScript | jQuery, custom `custom.js` |
| Tables | DataTables with Buttons extension (PDF/CSV/Excel) |
| Forms | jQuery Validate, Select2, Bootstrap Datepicker |
| Calendar | FullCalendar |
| File Upload | Bootstrap FileInput |
| Translation | Custom `i18n.js` engine (477 pairs, MutationObserver) |
| OCR | Tesseract.js (lazy-loaded on admission form) |

### Database
| Component | Technology |
|-----------|-----------|
| Engine | MySQL (via Laragon on Windows) |
| Database name | `lav_sms` |
| Migrations | 52 migration files |
| Models | 70 Eloquent models |

### Server Requirements
- PHP 8.0+ with extensions: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, gd
- MySQL 5.7+ or MariaDB 10.3+
- Composer 2.x
- Node.js (for asset compilation, optional)
- Ollama (optional, for AI features)

## 2.3 Folder Structure

```
sms/
├── app/
│   ├── Console/Commands/        # Artisan commands (GenerateAcademicCalendar)
│   ├── Exceptions/              # Global exception handler
│   ├── Helpers/
│   │   ├── Qs.php               # Global utility: auth checks, routing, settings
│   │   ├── Mk.php               # Mark/grade utilities (extends Qs)
│   │   └── Pay.php              # Payment reference utilities
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/            # Login, register, password reset
│   │   │   ├── Finance/         # Finance module controllers
│   │   │   ├── SupportTeam/     # Core school controllers (HR, marks, attendance...)
│   │   │   ├── SuperAdmin/      # Audit logs, rules, settings
│   │   │   └── MyParent/        # Parent portal controller
│   │   ├── Middleware/Custom/   # Role-based access middleware
│   │   └── Requests/            # Form request validation classes
│   ├── Models/                  # 70 Eloquent models
│   ├── Repositories/            # MyClassRepo, StudentRepo (data access layer)
│   └── Services/                # Business logic services
│       ├── AICommentService.php
│       ├── AttendanceRiskService.php
│       ├── AttendanceService.php
│       ├── EmployeeProfileService.php
│       ├── LeaveService.php
│       ├── PayrollService.php
│       ├── PerformanceAnalysisService.php
│       ├── RulesEngine.php
│       └── TimetableValidationService.php
├── database/
│   ├── migrations/              # 52 migration files
│   └── seeders/                 # DatabaseSeeder, UserSeeder
├── public/
│   └── assets/
│       ├── css/modern.css       # Custom styles + mobile responsive
│       └── js/
│           ├── i18n.js          # Amharic/English translation engine
│           └── custom.js        # App-wide JS (sidebar, alerts, etc.)
├── resources/views/
│   ├── auth/                    # Login, password reset views
│   ├── dashboard/               # Role-specific dashboard views
│   ├── pages/
│   │   ├── admin/               # Admin-only pages
│   │   ├── finance/             # Finance module views
│   │   ├── hr/                  # HR module views
│   │   ├── support_team/        # Shared academic views
│   │   └── teacher/             # Teacher-specific views
│   └── partials/                # Shared layout components
│       ├── menu.blade.php       # Role-aware sidebar navigation
│       ├── top_menu.blade.php   # Top navbar with language toggle
│       ├── inc_top.blade.php    # HTML head, CSS includes
│       └── inc_bottom.blade.php # JS includes (i18n.js before custom.js)
└── routes/
    └── web.php                  # All application routes (~400 lines)
```

## 2.4 Layer Interaction

```
routes/web.php
    defines route groups by middleware (teamSA, hr_manager, teacher, etc.)
    ↓
Middleware (e.g. HRManager::handle())
    checks Auth::user()->user_type against allowed roles
    ↓
Controller (e.g. HRController::index())
    validates input, calls Service layer
    ↓
Service (e.g. PayrollService::generate())
    contains business logic, calls Models/Repositories
    ↓
Model (e.g. StaffPayroll, Employee)
    Eloquent ORM — queries MySQL
    ↓
Blade View
    receives $data compact array, renders HTML
    ↓
Response to browser
```

The `Qs` helper class is used throughout controllers and views as a global utility for auth checks (`Qs::userIsTeamSA()`), routing (`Qs::goWithSuccess()`), settings (`Qs::getSetting()`), and ID hashing.


---

# 3. Functional Modules

## 3.1 Authentication & Role-Based Access Control

### Purpose
Controls who can log in and what they can access. Student accounts are blocked from direct login.

### Key Workflow
1. User submits `identity` field (email or username) + password
2. `LoginController::username()` detects whether identity is email or username using `FILTER_VALIDATE_EMAIL`
3. Laravel's `AuthenticatesUsers` trait handles credential verification
4. `authenticated()` hook fires post-login — if `user_type === 'student'`, the session is immediately destroyed and the user is redirected with an error
5. All other users are redirected to `/home` which routes to their role-specific dashboard

### Related Files
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Middleware/Custom/` (8 middleware classes)
- `app/Http/Kernel.php` (middleware registration)
- `app/Helpers/Qs.php` (role check methods)

### Middleware Classes

| Middleware Key | Class | Allowed Roles |
|---------------|-------|---------------|
| `auth` | Laravel built-in | Any authenticated user |
| `admin` | `Admin.php` | `admin` |
| `super_admin` | `SuperAdmin.php` | `super_admin` |
| `teamSA` | `TeamSA.php` | `admin`, `super_admin` |
| `teamSAT` | `TeamSAT.php` | `admin`, `super_admin`, `teacher` |
| `hr_manager` | `HRManager.php` | `hr_manager`, `admin`, `super_admin` |
| `teacher` | `Teacher.php` | `teacher` only |
| `my_parent` | `MyParent.php` | `parent` only |
| `examIsLocked` | `ExamIsLocked.php` | Blocks access if exam is locked in settings |

---

## 3.2 Student Management

### Purpose
Manages the full lifecycle of a student from admission to graduation.

### Inputs and Outputs
- **Input:** Personal data (name, DOB, gender, religion, region, phone), academic data (class, section, parent, year admitted), optional photo
- **Output:** Student record with auto-generated admission number (`STM-YYYY-XXXX`), student profile page, class lists

### Key Workflows

**Single Admission (2-step wizard):**
1. Step 1 — Personal Data: name, gender, DOB, nationality, region, sub-city, phone, blood group, religion, photo
2. Step 2 — Student Data: class, section, parent, year admitted, admission number (auto-generated)
3. On save: user account created, student_record created, admission number assigned

**Bulk Admission (CSV):**
1. Download CSV template with columns: name, gender, email, phone, dob, address, class_name, section_name, year_admitted, religion
2. Upload CSV (max 5MB, UTF-8)
3. Preview parsed rows before import
4. On confirm: each row creates a user + student_record; admission numbers auto-generated

**Promotion:**
- Admin selects source class → target class
- All non-graduated students in source class are moved to target class
- Old marks/exam records for the previous class are deleted via `Mk::deleteOldRecord()`

**Graduation:**
- Student record `grad` flag set to `1`, `grad_date` recorded
- Graduated students appear in a separate "Graduated" list

### Related Files
- `app/Http/Controllers/SupportTeam/StudentRecordController.php`
- `app/Http/Controllers/SupportTeam/PromotionController.php`
- `app/Models/StudentRecord.php`, `app/Models/User.php`
- `database/migrations/2018_09_22_151514_create_student_records_table.php`
- `database/migrations/2024_06_03_000001_ethiopianize_student_records.php`

### Ethiopian Customisations
- States/LGAs replaced with 13 Ethiopian regions + Addis Ababa sub-cities
- Phone validation: `^09[0-9]{8}$` (10 digits starting with 09)
- Religion field replaces Sports House
- Admission number format: `STM-YYYY-XXXX`
- Nationality defaults to Ethiopian

---

## 3.3 Academics — Classes, Sections, Subjects

### Purpose
Defines the school's academic structure that all other modules depend on.

### Class Types (from `class_types` table)
| Code | Name | Grading |
|------|------|---------|
| `C` | Creche | Descriptive |
| `PN` | Pre-Nursery | Descriptive |
| `N` | Nursery | Descriptive |
| `P` | Primary (Classes 1–4) | Letter grades (A+/A/B/C/D/F) |
| `UP` | Upper Primary (Classes 5–8) | Stricter letter grades |

### Grading Scales
**Nursery/Pre-Nursery/Creche (Descriptive):**
- 80–100: Excellent
- 60–79: Good
- 40–59: Satisfactory
- 0–39: Needs Improvement

**Primary (Classes 1–4):**
- A+ (90–100), A (80–89), B (70–79), C (60–69), D (50–59), F (0–49)

**Upper Primary (Classes 5–8) — stricter:**
- A+ (90–100), A (75–89), B (60–74), C (50–59), D (40–49), F (0–39)

### Related Files
- `app/Http/Controllers/SupportTeam/MyClassController.php`
- `app/Http/Controllers/SupportTeam/SectionController.php`
- `app/Http/Controllers/SupportTeam/SubjectController.php`
- `database/migrations/2024_06_05_000001_ethiopian_grading_system.php`

---

## 3.4 Academics — Exams and Marks

### Purpose
Manages the 2-semester exam system, mark entry, and report card generation.

### Mark Weight Distribution (Ethiopian MoE standard)
| Component | Max Score |
|-----------|-----------|
| Assessment (continuous) | 30 |
| Mid-term Exam | 20 |
| Final Exam | 50 |
| **Total** | **100** |

### Key Workflows

**Exam Creation:**
- Admin creates exams with `term` (1 or 2) and `year` (academic session)
- Only 2 semesters — no third term

**Mark Entry:**
1. Teacher/admin selects: Exam → Class → Section → Subject
2. System loads all students in that class/section
3. For each student: enter assessment (0–30), mid (0–20), final (0–50)
4. On save: `tex{term}` column in `marks` table is updated with total
5. Grade is auto-assigned based on class type grading scale

**Batch Fix:**
- Recalculates all `tex` totals and grade assignments for a given exam/class
- Used when grading scales are changed after marks are entered

**Marksheet (Bulk View):**
- Shows all students in a class with their marks per subject
- Exportable as PDF

**Tabulation Sheet:**
- Cross-tabulation of all students × all subjects for an exam
- Shows totals, averages, positions

### Related Files
- `app/Http/Controllers/SupportTeam/ExamController.php`
- `app/Http/Controllers/SupportTeam/MarkController.php`
- `app/Http/Controllers/SupportTeam/GradeController.php`
- `app/Helpers/Mk.php` (mark calculation utilities)
- `app/Models/Mark.php`, `app/Models/ExamRecord.php`, `app/Models/Grade.php`

---

## 3.5 Timetable

### Purpose
Creates and manages class and exam timetables with automated conflict detection.

### Key Workflows
1. Admin creates a timetable (name, class, type: class or exam)
2. Time slots are defined (period name, start time, end time, day of week)
3. Subjects are assigned to periods
4. Teachers are assigned to subjects
5. `TimetableValidationService` checks for 4 conflict types before saving

### Conflict Detection (4 Types)
1. **Teacher double-booking** — same teacher assigned to two classes at the same time
2. **Room/class overlap** — same class has two subjects in the same period
3. **Subject repetition** — same subject appears twice in the same day for a class
4. **Teacher workload** — teacher exceeds maximum periods per day

### Related Files
- `app/Http/Controllers/SupportTeam/TimeTableController.php`
- `app/Services/TimetableValidationService.php`
- `app/Models/TimeTable.php`, `app/Models/TimeTableRecord.php`, `app/Models/TimeSlot.php`

---

## 3.6 Attendance

### Purpose
Homeroom teachers mark daily student attendance. Admins view reports. AI service predicts dropout risk.

### Key Workflows

**Marking Attendance:**
1. Teacher navigates to Attendance → their homeroom section is shown
2. Teacher opens a session (selects class, section, date)
3. System validates: teacher must be assigned as homeroom teacher for that section
4. `RulesEngine::validateAttendanceSession()` checks for duplicate sessions
5. For each student: mark Present / Absent / Late
6. Records saved to `attendance_records` table

**Admin View:**
- Admins see all sessions but cannot mark attendance (read-only)
- Can view per-student attendance reports

**Dropout Early Warning:**
- `AttendanceRiskService` analyses all active students
- Returns risk assessments sorted by risk score (highest first)
- Only students with risk_score > 0 OR attendance < 75% are shown

### Related Files
- `app/Http/Controllers/SupportTeam/AttendanceController.php`
- `app/Services/AttendanceRiskService.php`
- `app/Services/RulesEngine.php`
- `app/Models/AttendanceSession.php`, `app/Models/AttendanceRecord.php`

---

## 3.7 HR Management System

### Purpose
Manages the complete employee lifecycle: profile, contract, qualifications, emergency contacts, status changes.

### Data Model
The HR module uses a **two-table identity pattern**:
- `employees` — core HR identity (personal data, status, compliance IDs)
- `employment_details` — contract terms (department, position, salary, hire date, bank details)

This separation allows contract changes to be tracked over time without overwriting personal data.

### Key Workflows

**Create Employee:**
1. HR Manager fills form: personal info, department, position, employment type, hire date, salary, bank details
2. `EmployeeProfileService::create()` generates employee code (`STF-0001`)
3. Creates `employees` record + `employment_details` record
4. Optionally links to a `users` account (nullable — non-teaching staff may not have system accounts)

**Employee Status Changes:**
- `active` → `on_leave` / `suspended` / `terminated`
- Termination records `termination_date` and `termination_reason`
- Reactivation resets status to `active`

**Qualifications:**
- Multiple qualifications per employee (degree, field, institution, graduation year)
- Stored in `employee_qualifications` table

**Emergency Contacts:**
- Multiple contacts per employee
- Stored in `employee_emergency_contacts` table

### Related Files
- `app/Http/Controllers/SupportTeam/HRController.php` (704 lines)
- `app/Services/EmployeeProfileService.php`
- `app/Models/Employee.php`, `app/Models/EmploymentDetails.php`
- `app/Models/EmployeeQualification.php`, `app/Models/EmployeeEmergencyContact.php`
- `database/migrations/2024_07_03_000001_create_hr_core_tables.php`

---

## 3.8 Payroll System

### Purpose
Generates monthly payroll for all active employees with Ethiopian statutory deductions.

### Payroll Fields (staff_payrolls table)
| Field | Description |
|-------|-------------|
| `month` | Pay period (Y-m format) |
| `period_start` / `period_end` | Exact date range |
| `working_days` | Total working days in period |
| `present_days` | Days employee was present |
| `absent_days` | Days absent |
| `leave_days` | Days on approved leave |
| `overtime_hours` | Overtime hours worked |
| `income_tax` | Ethiopian income tax deduction |
| `employee_pension` | Employee pension contribution (7%) |
| `employer_pension` | Employer pension contribution (11%) |
| `status` | draft → approved → paid |
| `approved_by` / `approved_at` | Approval audit trail |
| `paid_at` | Payment timestamp |

### Payroll Items (payroll_items table)
Line-item breakdown of each payroll:
- Type: `earning` or `deduction`
- Label: e.g. "Transport Allowance", "Income Tax"
- Amount

### Approval Workflow
1. HR generates payroll for a month → status: `draft`
2. HR reviews and approves → status: `approved`
3. HR marks as paid → status: `paid`

### Related Files
- `app/Http/Controllers/SupportTeam/HRController.php` (payroll section)
- `app/Http/Controllers/Finance/PayrollController.php`
- `app/Services/PayrollService.php`
- `app/Models/StaffPayroll.php`, `app/Models/PayrollItem.php`
- `database/migrations/2024_07_05_000001_enhance_payroll_step3.php`

---

## 3.9 Leave Management

### Purpose
Manages employee leave entitlements, requests, approvals, and running balances.

### Leave Types
`annual`, `sick`, `maternity`, `paternity`, `unpaid`, `other`

### Three-Table Design
| Table | Purpose |
|-------|---------|
| `leave_policies` | Entitlement rules per leave type per year (days_entitled, is_paid, carry_forward) |
| `leave_requests` | Individual requests with approval workflow |
| `leave_balances` | Running balance per employee/type/year (entitled, used, pending) |

### Key Workflows

**Policy Setup:**
1. HR creates a policy: leave_type + year + days_entitled
2. HR initialises balances for all active employees for that year
3. Each employee gets a `leave_balances` row per leave type

**Request Submission (HR-initiated):**
1. HR selects employee, leave type, start/end dates
2. `LeaveService::submit()` calculates `days_requested`
3. Checks balance: if insufficient, throws `RuntimeException`
4. Creates `leave_requests` record with status `pending`
5. Increments `leave_balances.pending`

**Self-Service (Staff):**
- Teachers and HR managers can submit their own leave requests via `/my/leave`
- `LeaveController::resolveMyEmployee()` links `auth()->id()` to `employees.user_id`
- Same validation and balance check applies

**Approval:**
1. HR approves request → status: `approved`
2. `leave_balances.used` incremented, `pending` decremented
3. Attendance records are automatically created for the leave period

**Rejection/Cancellation:**
- Status updated, `pending` balance decremented

### Related Files
- `app/Http/Controllers/SupportTeam/LeaveController.php`
- `app/Services/LeaveService.php`
- `app/Models/LeaveRequest.php`, `app/Models/LeaveBalance.php`, `app/Models/LeavePolicy.php`
- `database/migrations/2024_07_06_000001_create_leave_management_tables.php`

---

## 3.10 Recruitment System

### Purpose
Manages job postings and the candidate application pipeline.

### Pipeline Stages
`applied` → `shortlisted` → `interviewed` → `hired` / `rejected`

### Key Workflows

**Job Posting:**
1. HR creates posting: title, department, position, employment type, vacancies, deadline, description, requirements
2. Status: `open` / `on_hold` / `closed`

**Application Management:**
1. HR adds applications manually (external candidates)
2. Each application: name, email, phone, resume path, cover letter
3. HR moves applications through pipeline stages
4. `application_notes` table stores status change history and HR comments

### Related Files
- `app/Http/Controllers/SupportTeam/RecruitmentController.php`
- `app/Models/JobPosting.php`, `app/Models/JobApplication.php`, `app/Models/ApplicationNote.php`
- `database/migrations/2024_07_08_000001_create_recruitment_tables.php`

---

## 3.11 Performance Review System

### Purpose
Structured employee performance evaluation using weighted scoring categories.

### Scoring Formula
```
overall_score = sum(score_i × weight_i) / sum(weight_i)
```
Where each `score_i` is 0–10 and `weight_i` is the category weight.

### Key Workflows

**Setup:**
1. HR creates performance categories (e.g. "Punctuality", "Teaching Quality", "Teamwork")
2. Each category has a weight (e.g. 2.0, 3.0, 1.5)

**Review Creation:**
1. HR selects employee + period (Y-m format, e.g. "2024-07")
2. Enters score (0–10) for each active category
3. System calculates `weighted_score = score × weight` per category
4. `PerformanceReview::recalculate()` computes `overall_score`
5. One review per employee per period (unique constraint enforced)

**Grade Labels** (inferred from overall_score):
- 8.0–10.0: Excellent
- 6.0–7.9: Good
- 4.0–5.9: Satisfactory
- 0–3.9: Needs Improvement

**Self-Service:**
- Employees can view their own review history via `/my/performance`
- `myPerformance()` resolves the employee record linked to `auth()->id()`

### Related Files
- `app/Http/Controllers/SupportTeam/PerformanceController.php`
- `app/Models/PerformanceReview.php`, `app/Models/PerformanceScore.php`, `app/Models/PerformanceCategory.php`
- `database/migrations/2024_07_08_000002_create_performance_tables.php`

---

## 3.12 Finance Module

### Purpose
Manages student fee collection, expenses, other income, and financial reporting.

### Sub-modules

**Student Fees:**
1. `fee_categories` — define fee types (Tuition, Registration, Exam Fee, etc.)
2. `fee_structures` — assign amount per category per class per session
3. `student_fee_invoices` — one invoice per student per fee structure
4. `fee_payments` — individual payment transactions against invoices

**Invoice Lifecycle:**
- Created with status `unpaid`
- Partial payment → status `partial`
- Full payment → status `paid`
- `net_amount = original_amount - discount + fine`
- `balance = net_amount - amount_paid`

**Expenses:**
- Categorised expenses with date, amount, receipt number
- Linked to `expense_categories`

**Other Income:**
- Non-fee income (donations, grants, etc.)
- Linked to `income_categories`

**Finance Dashboard:**
- Shows: Fees Collected, Pending Fees, Total Expenses, Salary Paid, Other Income, Net Balance
- Monthly collection vs expenses chart
- Invoice status breakdown (paid/partial/unpaid)
- Recent payments table

### Related Files
- `app/Http/Controllers/Finance/StudentFeeController.php`
- `app/Http/Controllers/Finance/FinanceDashboardController.php`
- `app/Http/Controllers/Finance/ExpenseController.php`
- `app/Http/Controllers/Finance/IncomeController.php`
- `app/Models/FeeCategory.php`, `app/Models/FeeStructure.php`
- `app/Models/StudentFeeInvoice.php`, `app/Models/FeePayment.php`

---

## 3.13 Communication

### Purpose
Internal messaging between staff and announcements to all users.

### Features
- **Announcements** — broadcast messages visible to all roles
- **Inbox** — private messages between users
- **Compose** — send message to any user
- **Unread badge** — navbar shows count of unread messages
- **AI Summarisation** — when reading a message, `AICommentService::summarizeMessage()` generates a 1-2 sentence summary via Ollama

### Related Files
- `app/Http/Controllers/CommunicationController.php`
- `app/Models/Message.php`, `app/Models/Announcement.php`

---

## 3.14 Library

### Purpose
Manages book inventory and borrow/return workflow.

### Workflows
1. Admin adds books (title, author, quantity)
2. Students/teachers submit borrow requests
3. Admin approves request → book marked as borrowed
4. Return recorded → book quantity restored
5. History view shows all past transactions

### Related Files
- `app/Http/Controllers/SupportTeam/LibraryController.php`
- `app/Http/Controllers/SupportTeam/BookController.php`
- `app/Http/Controllers/SupportTeam/BookRequestController.php`
- `app/Models/Book.php`, `app/Models/BookRequest.php`


---

# 4. Algorithms and Logic

## 4.1 Attendance Risk Scoring Algorithm

**File:** `app/Services/AttendanceRiskService.php`

The algorithm assigns a risk score (0–100) to each active student based on 6 weighted factors:

```
Risk Score = sum of triggered factor weights (capped at 100)
```

### Factor Weights
| Factor | Weight | Trigger Condition |
|--------|--------|-------------------|
| `attendance_critical` | 30 | Attendance < 65% |
| `attendance_declining` | 20 | Recent attendance dropped > 10 percentage points |
| `grades_below_50` | 25 | Academic average < 50% |
| `attendance_warning` | 15 | Attendance 65–74% (below MoE minimum of 75%) |
| `grades_declining` | 15 | Grades dropped > 15 points vs previous exam |
| `consecutive_absences` | 10 | 5+ consecutive absent days |

### Trend Detection (Attendance)
1. Split all attendance records into two halves (recent vs older)
2. Calculate attendance percentage for each half
3. If recent% < older% - 5 → trend = "declining", drop_pp = older% - recent%
4. If recent% > older% + 5 → trend = "improving"
5. Otherwise → "stable"

### Risk Levels
- Score ≥ 50 → **critical** (red)
- Score 25–49 → **warning** (yellow)
- Score < 25 → **low** (green)

### Recommendations Generated
- Critical attendance: "Immediate parent conference required"
- Warning attendance: "Send attendance warning letter to parents"
- Declining trend: "Schedule check-in with student"
- Consecutive absences: "Immediate home visit or phone call"
- Low grades: "Assign to academic support programme"
- Declining grades: "Schedule academic counselling"

---

## 4.2 Performance Review Scoring Algorithm

**File:** `app/Services/PerformanceAnalysisService.php` + `app/Models/PerformanceReview.php`

### Formula
```
overall_score = Σ(score_i × weight_i) / Σ(weight_i)
```

**Example:**
- Category A: score=8, weight=3.0 → weighted=24.0
- Category B: score=6, weight=2.0 → weighted=12.0
- Category C: score=7, weight=1.5 → weighted=10.5
- overall_score = (24.0 + 12.0 + 10.5) / (3.0 + 2.0 + 1.5) = 46.5 / 6.5 = **7.15**

`PerformanceReview::recalculate()` is called after every score save/update.

---

## 4.3 Payroll Calculation Logic

**File:** `app/Services/PayrollService.php`

### Ethiopian Statutory Deductions

**Employee Pension:** 7% of basic salary
**Employer Pension:** 11% of basic salary

**Ethiopian Income Tax Brackets (EITB):**
| Monthly Taxable Income (ETB) | Tax Rate |
|------------------------------|----------|
| 0 – 600 | 0% |
| 601 – 1,650 | 10% |
| 1,651 – 3,200 | 15% |
| 3,201 – 5,250 | 20% |
| 5,251 – 7,800 | 25% |
| 7,801 – 10,900 | 30% |
| 10,901+ | 35% |

### Net Salary Formula
```
gross = basic_salary + allowances
taxable = gross - employee_pension
income_tax = apply_tax_bracket(taxable)
net_pay = gross - employee_pension - income_tax - other_deductions
```

### Attendance-Based Proration
```
daily_rate = basic_salary / working_days
absent_deduction = daily_rate × absent_days
prorated_salary = basic_salary - absent_deduction
```

---

## 4.4 Leave Balance Logic

**File:** `app/Services/LeaveService.php`

### Balance Fields
- `entitled` — from leave policy (e.g. 14 days annual)
- `used` — approved days taken
- `pending` — days in pending requests
- `available` = entitled - used - pending

### State Transitions
```
Submit request:   pending += days_requested
Approve request:  used += days_requested; pending -= days_requested
Reject request:   pending -= days_requested
Cancel request:   if was approved: used -= days_requested; else pending -= days_requested
```

### Validation
- `available >= days_requested` — checked before submission
- If insufficient: `RuntimeException` thrown with message "Insufficient leave balance"

---

## 4.5 AI Report Card Comment Generation

**File:** `app/Services/AICommentService.php`

### Pattern Detection (7 Patterns)
The system analyses the score distribution before calling the AI:

| Pattern | Trigger Condition |
|---------|-------------------|
| `strong_coursework_weak_exam` | coursework ratio ≥ 70% AND exam ratio < 50% |
| `strong_exam_weak_coursework` | exam ratio ≥ 70% AND coursework ratio < 50% |
| `significant_struggle` | total < 45 |
| `excellence` | total ≥ 85 |
| `significant_drop` | previous_total - current_total > 15 |
| `significant_improvement` | current_total - previous_total > 15 |
| `consistent` | none of the above |

Where:
- `coursework_ratio = (assessment + mid_exam) / 50`
- `exam_ratio = final_exam / 50`

### Prompt Construction
The prompt includes:
1. Student name, subject, all three score components
2. Performance level (excellent/good/satisfactory/needs improvement)
3. Previous term total (for trend context)
4. Attendance percentage
5. Evidence-based observation for the detected pattern
6. Recommended focus area

### Fallback
If Ollama is unavailable or times out (30s), a template-based fallback comment is returned based on performance level.

---

## 4.6 Academic Performance Analysis

**File:** `app/Services/PerformanceAnalysisService.php`

### At-Risk Student Detection
```
at_risk = students where:
  current_avg < min_score (default 50)
  OR drop_percent > threshold (default 15%)

drop_percent = ((previous_avg - current_avg) / previous_avg) × 100
```

### Risk Levels
- `current_avg < 40` OR `drop > 25%` → critical
- `current_avg < 50` OR `drop > 15%` → warning
- Otherwise → low

### Subject Alert Detection
- Subjects with class average < 50 (configurable threshold)
- OR subjects with declining trend (current avg < previous avg by > 5%)

---

## 4.7 Mark Calculation (Mk Helper)

**File:** `app/Helpers/Mk.php`

### Grade Assignment
```php
Mk::getGradeList($class_type_id)
```
Returns grades for the class type. Falls back to grades with `class_type_id = NULL` if none found.

### Distinction/Credit/Pass/Fail Counting
- Distinctions: grades starting with A or B
- Credits: grades starting with C
- Passes: grades starting with D or E
- Failures: grades starting with F

### Position Suffix
`Mk::getSuffix($number)` — adds ordinal suffix (1st, 2nd, 3rd, 4th...)

---

# 5. Database Design

## 5.1 Core Tables

### users
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| name | varchar | Full name |
| username | varchar unique | Login identifier |
| email | varchar unique | |
| password | varchar | bcrypt hashed |
| user_type | enum | super_admin, admin, teacher, hr_manager, student, parent |
| phone | varchar | |
| photo | varchar | File path |
| gender | varchar | |
| dob | date | |
| address | text | |

### student_records
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| user_id | int FK→users | |
| my_class_id | int FK→my_classes | |
| section_id | int FK→sections | |
| adm_no | varchar unique | STM-YYYY-XXXX |
| my_parent_id | int FK→users | nullable |
| session | varchar | Academic year e.g. 2024-2025 |
| year_admitted | varchar | |
| religion | varchar | Ethiopian localisation |
| age | tinyint | |
| grad | tinyint | 0=active, 1=graduated |
| grad_date | varchar | |

### my_classes
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| name | varchar | e.g. "Class 5", "KG 2" |
| class_type_id | int FK→class_types | |

### sections
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| my_class_id | int FK→my_classes | |
| name | varchar | e.g. "A", "B" |
| teacher_id | int FK→users | Homeroom teacher |

### subjects
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| name | varchar | |
| my_class_id | int FK→my_classes | |
| teacher_id | int FK→users | Assigned teacher |

## 5.2 Academic Tables

### exams
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| name | varchar | e.g. "Semester 1" |
| term | tinyint | 1 or 2 |
| year | varchar | Academic session |

### marks
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| student_id | int FK→users | |
| subject_id | int FK→subjects | |
| exam_id | int FK→exams | |
| my_class_id | int FK→my_classes | |
| section_id | int FK→sections | |
| year | varchar | |
| tca | decimal | Assessment (0–30) |
| exm | decimal | Mid-term (0–20) |
| tex1/tex2 | decimal | Final per term |
| grade_id | int FK→grades | Auto-assigned |

### grades
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| name | varchar | A+, A, B, C, D, F, Excellent, etc. |
| mark_from | int | Lower bound |
| mark_to | int | Upper bound |
| remark | varchar | |
| class_type_id | int FK→class_types | nullable = universal |

### attendance_sessions
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| my_class_id | int FK | |
| section_id | int FK | |
| teacher_id | int FK→users | |
| date | date | |
| year | varchar | |

### attendance_records
| Column | Type | Notes |
|--------|------|-------|
| id | int PK | |
| session_id | int FK→attendance_sessions | |
| student_id | int FK→users | |
| status | enum | present, absent, late |

## 5.3 HR Tables

### employees
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | int FK→users | nullable |
| employee_code | varchar unique | STF-0001 |
| first_name, last_name | varchar | |
| gender, dob, phone, email, address | various | |
| national_id, tin_number, pension_number | varchar | Ethiopian compliance |
| status | enum | active, on_leave, suspended, terminated |
| termination_date, termination_reason | date/text | |
| deleted_at | timestamp | Soft delete |

### employment_details
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| employee_id | bigint FK→employees | |
| department_id | bigint FK→departments | nullable |
| position_id | bigint FK→positions | nullable |
| reporting_manager_id | bigint FK→employees | self-referencing |
| employment_type | enum | full_time, part_time, contract, intern |
| hire_date, contract_end_date | date | |
| currency | varchar | ETB |
| salary | decimal | |
| bank_name, bank_account_no | varchar | |

### leave_requests
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| employee_id | bigint FK→employees | |
| leave_type | enum | annual, sick, maternity, paternity, unpaid, other |
| start_date, end_date | date | |
| days_requested | smallint | Calculated on create |
| status | enum | pending, approved, rejected, cancelled |
| reviewed_by | int FK→users | nullable |
| reviewed_at | timestamp | |
| review_comment | text | |

### staff_payrolls
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| employee_id | bigint FK→employees | |
| month | varchar | Y-m format |
| period_start, period_end | date | |
| working_days, present_days, absent_days, leave_days | smallint | |
| overtime_hours | decimal | |
| allowances, deductions | decimal | |
| income_tax, employee_pension, employer_pension | decimal | Ethiopian statutory |
| net_salary | decimal | |
| status | enum | draft, approved, paid |
| approved_by, approved_at, paid_at | int/timestamp | |

### performance_reviews
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| employee_id | bigint FK→employees | |
| reviewer_id | int FK→users | |
| period | varchar | Y-m format |
| overall_score | decimal | Calculated |
| notes | text | |
| UNIQUE | (employee_id, period) | One review per month |

### performance_scores
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| review_id | bigint FK→performance_reviews | |
| category_id | bigint FK→performance_categories | |
| score | decimal | 0–10 |
| weighted_score | decimal | score × weight |

### job_postings
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| title | varchar | |
| department_id, position_id | bigint FK | nullable |
| employment_type | enum | |
| vacancies | smallint | |
| deadline | date | |
| status | enum | open, closed, on_hold |

### job_applications
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| job_posting_id | bigint FK | |
| first_name, last_name, email, phone | varchar | |
| resume_path | varchar | |
| status | enum | applied, shortlisted, interviewed, hired, rejected |
| interview_date | date | |

## 5.4 Finance Tables

### fee_categories
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar | e.g. Tuition |
| code | varchar unique | e.g. TUI |
| active | boolean | |

### fee_structures
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| fee_category_id | bigint FK | |
| my_class_id | bigint FK | |
| session | varchar | Academic year |
| amount | decimal | |
| installments | int | Max allowed |
| UNIQUE | (fee_category_id, my_class_id, session) | |

### student_fee_invoices
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| invoice_no | varchar unique | |
| student_id | bigint FK→users | |
| fee_structure_id | bigint FK | |
| original_amount, discount, fine, net_amount | decimal | |
| amount_paid, balance | decimal | |
| status | enum | unpaid, partial, paid |
| due_date | date | |

### fee_payments
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| receipt_no | varchar unique | |
| invoice_id | bigint FK | |
| student_id | bigint FK→users | |
| collected_by | bigint FK→users | |
| amount | decimal | |
| installment_no | int | |
| payment_method | enum | cash, bank_transfer, mobile_money, chapa |
| paid_at | timestamp | |

## 5.5 Key Relationships

```
users (1) ──────────── (1) student_records
users (1) ──────────── (1) employees
employees (1) ──────── (1) employment_details
employees (1) ──────── (many) leave_requests
employees (1) ──────── (many) leave_balances
employees (1) ──────── (many) performance_reviews
employees (1) ──────── (many) staff_payrolls
employees (1) ──────── (many) staff_attendances
performance_reviews (1) ── (many) performance_scores
performance_scores (many) ── (1) performance_categories
job_postings (1) ──────── (many) job_applications
job_applications (1) ──── (many) application_notes
my_classes (1) ──────── (many) sections
sections (1) ──────── (many) student_records
subjects (many) ──────── (1) my_classes
marks (many) ──────── (1) students, exams, subjects
attendance_sessions (1) ── (many) attendance_records
fee_structures (1) ──── (many) student_fee_invoices
student_fee_invoices (1) ── (many) fee_payments
departments (1) ──────── (many) positions
departments (1) ──────── (many) employment_details
```


---

# 6. API and Routing

## 6.1 Route Groups

All routes are in `routes/web.php`. There is no REST API — all routes return HTML views or redirects, with a few JSON responses for AJAX calls.

### Public Routes
```
GET  /login                     → LoginController@showLoginForm
POST /login                     → LoginController@login
POST /logout                    → LoginController@logout
GET  /password/reset            → ForgotPasswordController
```

### Authenticated Routes (all roles)
```
GET  /home                      → HomeController@index (role-based redirect)
GET  /dashboard                 → role-specific dashboard
GET  /my_account                → MyAccountController
GET  /announcements             → CommunicationController@announcements
GET  /inbox                     → CommunicationController@inbox
GET  /compose                   → CommunicationController@compose
POST /messages                  → CommunicationController@store
GET  /messages/{id}             → CommunicationController@read
```

### Admin/Super Admin Routes (middleware: teamSA)
```
GET  /students/create           → StudentRecordController@create
POST /students                  → StudentRecordController@store
GET  /students/{class}          → StudentRecordController@index
GET  /students/{id}/show        → StudentRecordController@show
GET  /students/{id}/edit        → StudentRecordController@edit
PUT  /students/{id}             → StudentRecordController@update
DELETE /students/{id}           → StudentRecordController@destroy
GET  /students/promotion        → PromotionController@index
POST /students/promotion        → PromotionController@store
GET  /exams                     → ExamController@index
POST /exams                     → ExamController@store
GET  /grades                    → GradeController@index
GET  /classes                   → MyClassController@index
GET  /sections                  → SectionController@index
GET  /subjects                  → SubjectController@index
GET  /users                     → UserController@index
GET  /reports/*                 → ReportController
GET  /rules                     → RuleController@index
GET  /audit                     → AuditLogController@index
GET  /settings                  → SettingController (super_admin only)
```

### Teacher + Admin Routes (middleware: teamSAT)
```
GET  /marks                     → MarkController@index
POST /marks/manage              → MarkController@manage
POST /marks/update              → MarkController@update
GET  /marks/bulk                → MarkController@bulk
GET  /marks/show/{id}           → MarkController@show
GET  /marks/batch-fix           → MarkController@batchFix
GET  /marks/tabulation          → MarkController@tabulation
GET  /timetables                → TimeTableController@index
GET  /attendance                → AttendanceController@index
GET  /attendance/sessions       → AttendanceController@sessions
GET  /attendance/report/{id}    → AttendanceController@report
```

### Teacher-Only Write Routes (middleware: teacher)
```
POST /attendance/create         → AttendanceController@create
GET  /attendance/manage/{id}    → AttendanceController@manage
POST /attendance/store/{id}     → AttendanceController@store
```

### HR Manager Routes (middleware: hr_manager)
```
GET  /hr                        → HRController@index
GET  /hr/employees/create       → HRController@createEmployee
POST /hr/employees              → HRController@storeEmployee
GET  /hr/{id}                   → HRController@show
GET  /hr/{id}/edit              → HRController@editProfile
PUT  /hr/{id}                   → HRController@updateProfile
POST /hr/{id}/terminate         → HRController@terminateEmployee
POST /hr/{id}/reactivate        → HRController@reactivateEmployee
GET  /hr/departments            → HRController@departments
POST /hr/departments            → HRController@storeDepartment
GET  /hr/positions              → HRController@positions
GET  /hr/shifts                 → HRController@shifts
GET  /hr/attendance             → HRController@attendance
POST /hr/attendance/save        → HRController@saveAttendance
GET  /hr/attendance/{id}/report → HRController@attendanceReport
GET  /hr/payroll                → HRController@payroll
POST /hr/payroll/generate       → HRController@generatePayroll
POST /hr/payroll/{id}/approve   → HRController@approvePayroll
POST /hr/payroll/{id}/paid      → HRController@markPaid
GET  /hr/leave/requests         → LeaveController@requests
POST /hr/leave/requests         → LeaveController@storeRequest
GET  /hr/leave/requests/{id}    → LeaveController@showRequest
POST /hr/leave/{id}/approve     → LeaveController@approveRequest
POST /hr/leave/{id}/reject      → LeaveController@rejectRequest
GET  /hr/leave/balances         → LeaveController@balances
GET  /hr/leave/policies         → LeaveController@policies
POST /hr/leave/policies         → LeaveController@storePolicy
GET  /hr/recruitment/postings   → RecruitmentController@postings
POST /hr/recruitment/postings   → RecruitmentController@storePosting
GET  /hr/recruitment/applications → RecruitmentController@applications
GET  /hr/performance/reviews    → PerformanceController@reviews
POST /hr/performance/reviews    → PerformanceController@storeReview
GET  /hr/performance/categories → PerformanceController@categories
GET  /finance/dashboard         → FinanceDashboardController@index
GET  /finance/fees/*            → StudentFeeController
GET  /finance/expenses/*        → ExpenseController
GET  /finance/income/*          → IncomeController
GET  /finance/reports           → FinanceReportController@index
```

### Self-Service Routes (all authenticated staff)
```
GET  /my/leave                  → LeaveController@myLeaveIndex
GET  /my/leave/create           → LeaveController@myLeaveCreate
POST /my/leave                  → LeaveController@myLeaveStore
GET  /my/leave/{id}             → LeaveController@myLeaveShow
POST /my/leave/{id}/cancel      → LeaveController@myLeaveCancel
GET  /my/performance            → PerformanceController@myPerformance
GET  /my/payslips               → HRController@myPayslips
GET  /my/profile                → MyProfileController@index
GET  /my/job-board              → RecruitmentController@jobBoard
```

### Parent Routes (middleware: my_parent)
```
GET  /parent/dashboard          → MyController@index
GET  /parent/child/{id}         → MyController@child
```

## 6.2 AJAX / JSON Endpoints
```
GET  /ajax/sections/{class_id}  → AjaxController@sections
GET  /ajax/subjects/{class_id}  → AjaxController@subjects
GET  /hr/positions/{dept_id}    → HRController@positionsByDepartment
POST /hr/departments            → returns JSON {ok, msg, id, name}
POST /hr/positions              → returns JSON {ok, msg, id, name, dept}
POST /hr/shifts                 → returns JSON {ok, msg}
POST /ai/comment                → AICommentController@generate (JSON)
```

---

# 7. User Roles and Permissions

## 7.1 Role Definitions

### super_admin
- **Can:** Everything — all modules, system settings, user management, audit logs
- **Cannot:** Nothing is restricted
- **Dashboard:** Admin dashboard with full stats
- **Unique:** Only super_admin can access `/settings` (system configuration)

### admin
- **Can:** All academic modules, student management, HR (read + write), reports, audit logs
- **Cannot:** System Settings page
- **Dashboard:** Admin dashboard (same as super_admin minus settings link)

### teacher
- **Can:** Enter marks for their subjects, mark attendance for their homeroom class, view timetables, view student list, library, self-service HR (leave, payslips, performance, job board)
- **Cannot:** Student admission/edit, exam creation, grade management, HR admin, finance
- **Dashboard:** Teacher dashboard (my subjects, today's sessions, recent announcements)
- **Attendance restriction:** Can only mark attendance for sections where `sections.teacher_id = auth()->id()`

### hr_manager
- **Can:** Full HR module (employees, payroll, leave, recruitment, performance), full Finance module, self-service HR
- **Cannot:** Academic modules (marks, exams, timetable), student management, system settings
- **Dashboard:** HR Manager dashboard (staff stats, attendance summary, recent payments)

### parent
- **Can:** View their child's profile, marks, attendance report
- **Cannot:** Any write operation, any other student's data
- **Dashboard:** Parent portal showing linked children

### student
- **Cannot log in** — blocked at `LoginController::authenticated()`
- Managed entirely through the parent portal

## 7.2 Access Control Enforcement

Access control is enforced at **three levels**:

**Level 1 — Route Middleware** (in `routes/web.php`):
```php
Route::group(['middleware' => 'hr_manager'], function() {
    // HR routes — only hr_manager, admin, super_admin can reach these
});
```

**Level 2 — Controller Constructor**:
```php
public function __construct() {
    $this->middleware('hr_manager');
    // or
    $this->middleware('hr_manager')->except(['myLeaveIndex', ...]);
}
```

**Level 3 — In-method checks** (for fine-grained control):
```php
// Teacher homeroom check
$isHomeroom = Section::where('id', $req->section_id)
    ->where('teacher_id', Auth::id())
    ->exists();
if (!$isHomeroom) return back()->with('pop_error', '...');

// Parent child ownership check
Qs::userIsMyChild($student_id, Auth::id())
```

**Level 4 — View-level** (menu visibility):
```blade
@if(Qs::userIsTeamSA())
    {{-- Admin-only menu items --}}
@endif
@if(Qs::userIsHRManager())
    {{-- HR menu items --}}
@endif
```

---

# 8. UI/UX Structure

## 8.1 Dashboard Differences Per Role

### Admin Dashboard (`pages/admin/dashboard.blade.php`)
- Stat cards: Total Students, Total Teachers, Avg Attendance, Fees Cleared, Fees Outstanding
- Quick Actions: Admit, Attendance, Marks, Reports, Announce, Inbox
- Recent Announcements
- Recent Payments table
- Upcoming Exams

### HR Manager Dashboard (`pages/hr_manager/dashboard.blade.php`)
- Stat cards: Total Staff, Present Today, Absent Today, Fees Collected, Outstanding, Students Unpaid
- Quick Actions: Staff List, Finance Rpt, Departments, Payroll, Inbox, Staff Att.
- Recent Payments
- Leave requests summary

### Teacher Dashboard (`pages/teacher/dashboard.blade.php`)
- Stat cards: My Subjects, Today's Sessions, Unread Messages, Total Parents
- Quick Actions: Attendance, Marksheet, Library, Timetable
- No announcements yet / Recent Announcements
- No exams scheduled / Upcoming Exams

## 8.2 Navigation Structure

The sidebar (`partials/menu.blade.php`) is role-aware using Blade conditionals:

```
@if(Qs::userIsTeamSA())        → Admin/Super Admin menu
@if(Qs::userIsTeacher())       → Teacher menu
@if(Qs::userIsHRManager())     → HR Manager menu
```

All roles see:
- Dashboard link
- Communication section (Announcements, Inbox, Compose)
- My Account
- Sign Out

## 8.3 Key Pages and Flows

| Page | Route | Description |
|------|-------|-------------|
| Login | `/login` | Username or email + password |
| Dashboard | `/dashboard` | Role-specific |
| Student Admission | `/students/create` | 2-step wizard with OCR |
| Mark Entry | `/marks` | Select exam/class/section/subject → enter marks |
| Attendance | `/attendance` | Teacher marks homeroom; admin views |
| HR Employees | `/hr` | Staff list with status filter and search |
| Leave Requests | `/hr/leave/requests` | Pending/approved/rejected filter |
| Payroll | `/hr/payroll` | Monthly payroll with draft/approved/paid status |
| Finance Dashboard | `/finance/dashboard` | Revenue/expense overview |
| Fee Invoices | `/finance/fees/invoices` | Student fee management |
| Performance Reviews | `/hr/performance/reviews` | Monthly review list |
| Risk Analysis | `/attendance/risk` | Dropout early warning dashboard |

## 8.4 Mobile Responsiveness

- Sidebar slides off-screen on mobile (CSS transform)
- `#mobile-sidebar-toggle` button in top navbar
- Overlay closes sidebar on tap
- Full-width content on small screens
- Implemented in `public/assets/css/modern.css`

## 8.5 Amharic/English Translation

- Toggle button (`EN`/`አማ`) in top navbar
- `i18n.js` engine scans all text nodes on page load
- 477 translation pairs covering all modules
- MutationObserver translates dynamically loaded content (DataTables, modals)
- Language preference saved in `localStorage`

---

# 9. System Workflows

## 9.1 Employee Lifecycle (Recruit → Hire → Manage)

```
1. HR creates Job Posting (status: open)
2. Applications received → HR adds to system
3. HR moves applications through pipeline:
   applied → shortlisted → interviewed → hired
4. On hire: HR creates Employee record
   - employee_code auto-generated (STF-0001)
   - employment_details created (department, position, salary)
   - Optional: link to users account
5. HR assigns shift, salary, emergency contacts, qualifications
6. Monthly: HR generates payroll
7. HR conducts performance reviews (monthly)
8. If leave needed: employee submits request → HR approves
9. Status changes: active → on_leave / suspended / terminated
10. On termination: termination_date and reason recorded
```

## 9.2 Leave Request Process

```
1. Employee (or HR on behalf) submits leave request
   - Selects: leave_type, start_date, end_date
   - System calculates days_requested
   - Checks leave_balances.available >= days_requested
   - If insufficient: error shown, request blocked
2. Request created with status: pending
   - leave_balances.pending += days_requested
3. HR reviews request (showRequest page shows balance context)
4a. Approve:
   - status → approved
   - leave_balances.used += days_requested
   - leave_balances.pending -= days_requested
   - Attendance records created for leave period
4b. Reject:
   - status → rejected
   - leave_balances.pending -= days_requested
5. Employee can cancel pending request:
   - status → cancelled
   - leave_balances.pending -= days_requested
```

## 9.3 Payroll Processing

```
1. HR navigates to Payroll → selects month
2. Clicks "Generate" for the month
   - PayrollService reads all active employees
   - For each employee:
     a. Reads attendance data for the month
     b. Calculates working_days, present_days, absent_days, leave_days
     c. Reads current salary from staff_salaries
     d. Calculates prorated salary (if absences)
     e. Calculates income_tax (Ethiopian brackets)
     f. Calculates employee_pension (7%) and employer_pension (11%)
     g. Creates staff_payrolls record with status: draft
     h. Creates payroll_items for each earning/deduction line
3. HR reviews payroll table
4. HR approves: status → approved, approved_by/at recorded
5. HR marks as paid: status → paid, paid_at recorded
6. Employees can view their payslips via /my/payslips
```

## 9.4 Attendance Tracking

```
1. Teacher logs in → navigates to Attendance
2. System shows their homeroom sections
3. Teacher selects section + date → clicks "Open Session"
4. RulesEngine validates: no duplicate session for same class/section/date
5. AttendanceSession created (or retrieved if exists)
6. Teacher sees student list with Present/Absent/Late radio buttons
7. Teacher submits → AttendanceRecord created per student
8. AuditLog entry created
9. Admin can view all sessions and per-student reports
10. AttendanceRiskService runs on demand to identify at-risk students
```

## 9.5 Student Fee Collection

```
1. Admin creates fee_categories (e.g. Tuition, Registration)
2. Admin creates fee_structures (category + class + session + amount)
3. Admin assigns fee to student → student_fee_invoice created
   - invoice_no auto-generated
   - net_amount = original - discount + fine
   - balance = net_amount
   - status = unpaid
4. HR/Admin collects payment:
   - Selects invoice, enters amount, payment method
   - fee_payment record created with receipt_no
   - invoice.amount_paid += payment.amount
   - invoice.balance -= payment.amount
   - If balance = 0: status → paid
   - If 0 < balance < net_amount: status → partial
5. Receipt can be printed/downloaded
```

---

# 10. Security and Validation

## 10.1 Authentication

- Laravel's built-in `AuthenticatesUsers` trait
- Passwords stored as bcrypt hashes (cost factor 10)
- Login accepts username OR email (auto-detected)
- Student accounts blocked post-authentication
- Session-based authentication (no JWT/tokens)
- CSRF protection on all POST/PUT/DELETE routes via `VerifyCsrfToken` middleware

## 10.2 Input Validation

All form inputs are validated using Laravel's validation rules:

```php
// Example: Employee creation
$req->validate([
    'first_name'      => 'required|string|max:80',
    'email'           => 'nullable|email|max:100',
    'national_id'     => 'nullable|string|max:50',
    'department_id'   => 'nullable|exists:departments,id',
    'salary'          => 'nullable|numeric|min:0',
    'hire_date'       => 'nullable|date',
]);
```

Ethiopian phone validation: `regex:/^09[0-9]{8}$/`

## 10.3 Role Protection

Every sensitive route is protected by middleware. The middleware pattern:
```php
public function handle($request, Closure $next)
{
    if (Auth::check() && in_array(Auth::user()->user_type, ['hr_manager', 'admin', 'super_admin'])) {
        return $next($request);
    }
    return redirect()->route('dashboard')->with('flash_danger', 'Access denied.');
}
```

## 10.4 SQL Injection Prevention

All database queries use Eloquent ORM or Laravel's query builder with parameterised bindings. No raw SQL with user input.

## 10.5 XSS Protection

- Blade templates use `{{ }}` (auto-escaped) by default
- `{!! !!}` (unescaped) used only for trusted HTML (flash messages from `Qs::displaySuccess()`)

## 10.6 ID Obfuscation

Sensitive IDs in URLs are hashed using `hashids/hashids`:
```php
Qs::hash($id)       // encode
Qs::decodeHash($str) // decode
```
Salt: `date('dMY') . 'CJ'` (changes daily)

## 10.7 Audit Logging

Every create/update/delete action in HR and Finance is logged:
```php
AuditLog::log('created', 'hr', "Employee created: {$employee->employee_code}");
```
Stored in `audit_logs` table with user_id, action, module, description, timestamp.

---

# 11. Assumptions and Limitations

## 11.1 Missing or Incomplete Features

- **Chapa payment integration** — `ChapaController.php` exists but integration is not fully implemented
- **Email notifications** — No email sent on leave approval, payroll generation, or new messages
- **Push notifications** — No real-time notifications
- **Student login portal** — Students cannot log in; parent portal is the only access point
- **Multi-school support** — System is single-tenant (one school only)
- **Timetable auto-generation** — Conflict detection exists but timetables must be created manually
- **Biometric attendance** — Staff attendance is manually entered; no biometric device integration
- **Payroll bank file export** — Payroll can be exported to PDF/CSV but not to bank-specific formats

## 11.2 Constraints

- **Windows-only development** — Laragon on Windows; deployment to Linux requires path adjustments
- **Ollama dependency** — AI features require a locally running Ollama instance; graceful fallback exists
- **Single academic session** — System uses `Qs::getCurrentSession()` from settings; no multi-year parallel operation
- **No API** — All communication is server-rendered; no mobile app support
- **File storage** — All uploads stored in `public/uploads/`; no cloud storage (S3, etc.)
- **No automated backups** — Database backup must be done manually

---

# 12. Suggestions for Improvement

## 12.1 Performance Improvements

- **Eager loading** — Some views may trigger N+1 queries; add `with()` clauses to all list queries
- **Database indexing** — Add composite indexes on `(student_id, year)` in marks/exam_records, `(employee_id, month)` in staff_payrolls
- **Caching** — Cache `Qs::getCurrentSession()`, class lists, and grade lists (change infrequently)
- **Queue jobs** — Move payroll generation and bulk CSV import to background jobs using Laravel Queue
- **Pagination** — Ensure all large lists use `paginate()` not `get()` (some lists may load all records)

## 12.2 Scalability Ideas

- **Multi-tenancy** — Add `school_id` to all tables to support multiple schools from one installation
- **API layer** — Build a REST API (Laravel Sanctum) to support a mobile app
- **Cloud storage** — Move file uploads to S3 or similar for scalability
- **Microservices** — Extract AI features into a separate Python/FastAPI service
- **Redis** — Use Redis for session storage and caching in production

## 12.3 Feature Enhancements

- **Email notifications** — Send emails on leave approval/rejection, payroll generation, new messages
- **SMS notifications** — Integrate with Ethiopian SMS providers (Ethio Telecom API) for parent alerts
- **Biometric integration** — Connect to fingerprint scanners for automated staff attendance
- **Parent mobile app** — React Native app consuming the REST API
- **Online fee payment** — Complete Chapa integration for online student fee payment
- **Report card PDF** — Auto-generate formatted report cards with AI comments per student
- **Academic calendar automation** — Auto-generate holidays and exam schedules from rules
- **Two-factor authentication** — Add TOTP 2FA for admin and HR manager accounts
- **Data export** — Full database export to Excel for MoE reporting requirements
- **Bulk payroll bank file** — Generate bank-specific payment files (CBE, Awash, Dashen formats)
- **Student self-service** — Allow students to view their own marks and attendance via a dedicated portal
- **Timetable auto-generation** — Implement constraint-based timetable generation algorithm

