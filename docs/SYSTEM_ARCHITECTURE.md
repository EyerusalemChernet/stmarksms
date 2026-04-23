# St. Mark School ERP - System Architecture Document

Version: 1.0 | Date: 2026 | Project: Final Year ERP


---

## 1. SYSTEM OVERVIEW

### Purpose
St. Mark School ERP is a web-based Enterprise Resource Planning system built specifically for St. Mark Primary School in Addis Ababa, Ethiopia. It replaces manual paper-based processes with a centralised digital platform covering student management, academics, attendance, finance, HR, library, and parent communication.

### Target Users
- **School Administration** (Super Admin, Admin) - Addis Ababa, Ethiopia
- **Teaching Staff** (Teachers) - homeroom and subject teachers
- **HR & Finance Staff** (HR Manager) - staff management and fee collection
- **Parents/Guardians** - monitoring their children remotely
- **Students** - indirect users (data managed by staff and parents)

### High-Level Feature Summary
| Category | Features |
|---|---|
| Students | Admission, profiles, promotion, graduation |
| Academics | Classes, subjects, exams, marks, timetable |
| Attendance | Homeroom-based marking, sessions, reports |
| Finance | Fee management, payments, Chapa integration |
| HR | Staff list, departments, staff attendance |
| Library | Book catalog, borrow/return, history |
| Communication | Announcements, internal messaging |
| Reports | PDF/CSV exports for all modules |
| AI Features | Comment generation, risk prediction, OCR |
| Parent Portal | Child monitoring, fee payment, timeline |

---

## 2. TECHNOLOGY STACK

### Backend
- **Framework:** Laravel 8.x
- **Language:** PHP 8.3 (Laragon on Windows)
- **Key Packages:**
  - arryvdh/laravel-dompdf ^2.0 - PDF generation
  - hashids/hashids ^4.1 - URL ID obfuscation
  - guzzlehttp/guzzle ^7.0 - HTTP client (Ollama AI, Chapa)
  - laravel/ui ^3.0 - Auth scaffolding
  - ruitcake/laravel-cors ^2.0 - CORS headers

### Frontend
- **Templating:** Laravel Blade
- **CSS Framework:** Bootstrap 4 (legacy theme) + custom modern.css overlay
- **Icons:** Bootstrap Icons 1.11 (CDN)
- **JavaScript:** jQuery 3.x, jQuery Steps (wizard), Select2, DataTables, SweetAlert2, PNotify
- **Fonts:** Inter (Google Fonts)
- **OCR:** Tesseract.js 5 (lazy-loaded from CDN, client-side only)

### Database
- **Engine:** MySQL 8.4
- **ORM:** Eloquent (Laravel)
- **Pattern:** Repository Pattern (Controllers -> Repositories -> Models)

### AI / ML
- **Runtime:** Ollama (local, no cloud dependency)
- **Model:** TinyLlama (pulled via ollama pull tinyllama)
- **API:** REST at http://127.0.0.1:11434/api/generate
- **Integration:** GuzzleHttp direct POST, no Laravel package needed

### Server Requirements
- PHP 8.0+ with extensions: pdo_mysql, mbstring, fileinfo, gd, curl, zip, openssl
- MySQL 5.7+ or 8.x
- Composer 2.x
- 512MB RAM minimum, 2GB recommended
- Ollama (optional, for AI features)

---

## 3. SYSTEM ARCHITECTURE

### Repository Pattern
Every module follows: **Controller -> Repository -> Model**

`
HTTP Request
    |
    v
Middleware (auth, role check)
    |
    v
Controller (handles HTTP, calls repository)
    |
    v
Repository (database queries, business logic)
    |
    v
Eloquent Model (table mapping, relationships)
    |
    v
MySQL Database
`

Key repositories: UserRepo, StudentRepo, MarkRepo, ExamRepo, PaymentRepo, MyClassRepo, LocationRepo, TimeTableRepo

### Middleware Stack
Every request passes through:
1. TrustProxies - proxy headers
2. HandleCors - CORS
3. PreventRequestsDuringMaintenance
4. ValidatePostSize
5. TrimStrings
6. ConvertEmptyStringsToNull
7. EncryptCookies
8. StartSession
9. ShareErrorsFromSession
10. VerifyCsrfToken
11. **Role middleware** (custom, applied per route group)

### Role-Based Access Control (RBAC)
Roles are stored as plain strings in users.user_type. No Spatie/Gates — all checks use the Qs helper class.

**Middleware aliases registered in Kernel.php:**
| Alias | Class | Allows |
|---|---|---|
| uth | Laravel built-in | Any authenticated user |
| super_admin | Custom\SuperAdmin | super_admin only |
| 	eamSA | Custom\TeamSA | super_admin, admin |
| 	eamSAT | Custom\TeamSAT | super_admin, admin, teacher |
| hr_manager | Custom\HRManager | hr_manager only |
| 	eacher | Custom\Teacher | teacher only |
| my_parent | Custom\MyParent | parent only |
| examIsLocked | Custom\ExamIsLocked | checks exam lock flag |

### Directory Structure
`
app/
  Helpers/          Qs.php (utilities), Mk.php (mark calculations), Pay.php
  Http/
    Controllers/
      Auth/         Login, Register, Password reset
      SupportTeam/  All main modules (Students, Marks, Attendance, etc.)
      SuperAdmin/   Settings, Rules, Audit Logs
      MyParent/     Parent portal
      AICommentController.php
    Middleware/Custom/  All role middleware classes
    Requests/       Form validation (StudentRecordCreate, UserRequest, etc.)
  Models/           All Eloquent models
  Repositories/     All repository classes
  Services/         Business logic services (AI, Risk, Validation)
database/
  migrations/       All table definitions
  seeders/          Default data (users, grades, classes, etc.)
resources/views/
  layouts/          master.blade.php
  partials/         menu, top_menu, header, inc_top, inc_bottom
  pages/
    support_team/   All staff-facing views
    parent/         Parent portal views
    admin/          Admin dashboard
    teacher/        Teacher dashboard
    hr_manager/     HR Manager dashboard
    reports/        Report views
public/assets/
  css/              modern.css, qs.css
  js/               app.js, custom.js
docs/               Documentation files
`

---

## 4. USER ROLES & PERMISSIONS

### super_admin
**Can access:** Everything — all modules, system settings, rules engine, audit logs, PIN management, user deletion
**Cannot access:** Nothing is blocked
**Dashboard:** 8 stat cards (students, teachers, attendance %, fees, sessions, parents, messages)
**Special:** Only role that can delete users and exams; manages system settings

### admin
**Can access:** Students, academics, timetable, library, attendance (view only), reports (except finance), HR (view only), communication
**Cannot access:** System settings, rules engine, audit logs, PIN management, HR/Finance write operations
**Dashboard:** Same 8 stat cards as super_admin
**Note:** Cannot access /hr/* or /payments/* routes

### teacher
**Can access:** Enter marks (own subjects), mark attendance (homeroom only), view student list, library, communication
**Cannot access:** Student admission/edit, exam creation, timetable editing, finance, HR, reports
**Dashboard:** Assigned subjects, today's sessions, parent messages, upcoming exams
**Restriction:** Attendance write routes protected by 	eacher middleware

### hr_manager
**Can access:** HR module (staff list, departments, staff attendance, workload), payments, finance reports
**Cannot access:** Students, academics, marks, timetable, student attendance, library, rules engine
**Dashboard:** Staff counts, attendance today, fees collected/outstanding, recent payments
**Note:** Completely separate from academic admin — enforced at route level

### parent
**Can access:** Own children's data only — attendance, exam results, fees, library borrows, timeline
**Cannot access:** Any staff-facing module
**Dashboard:** Dedicated parent portal with child cards
**Restriction:** my_parent middleware; student login is blocked entirely

---

## 5. MODULE BREAKDOWN

### 5.1 Student Management

**Purpose:** Complete student lifecycle from admission to graduation.

**Database tables:** `users`, `student_records`, `promotions`

**Key fields in student_records:**
- `user_id` - links to users table
- `my_class_id`, `section_id` - current class placement
- `adm_no` - admission number (format: STM-YYYY-XXXX, auto-generated)
- `my_parent_id` - linked parent user
- `year_admitted` - academic year of entry
- `religion` - replaces old "house/sports house" field
- `grad` - 0=active, 1=graduated

**Controllers:** `StudentRecordController`, `PromotionController`

**Admission workflow:**
1. Admin opens multi-step wizard (jQuery Steps, 2 fieldsets)
2. Step 1: Personal data (name, DOB, gender, phone, nationality, region, sub-city, blood group, photo)
3. Step 2: Academic data (class, section, parent, year, religion)
4. On submit: auto-generates `STM-{YEAR}-{4-digit-sequence}` admission number
5. Creates `users` record + `student_records` record
6. Password set to "student" (blocked from logging in)

**Promotion logic (RulesEngine):**
- Validates class progression order: Nursery -> LKG -> UKG -> Class 1 -> ... -> Class 8
- Blocks same-class promotion, backwards promotion, class-skipping
- `PromotionController::selector()` calls `RulesEngine::validatePromotion()`

**Ethiopian customizations:**
- State dropdown replaced with 13 Ethiopian regions
- LGA replaced with Addis Ababa sub-cities (11) + regional zones
- Phone validation: `^09[0-9]{8}$` (Ethiopian mobile format)
- Religion field: Orthodox, Muslim, Protestant, Catholic, Traditional, Other
- Admission number: `STM-YYYY-XXXX` (sequential per year)
- Nationality defaults to "Ethiopian"

---

### 5.2 Academics - Classes & Subjects

**Purpose:** Define the school structure — classes, sections, subjects, teacher assignments.

**Database tables:** `my_classes`, `class_types`, `sections`, `subjects`

**Class types (class_types table):**
| ID | Code | Name |
|---|---|---|
| 1 | C | Creche |
| 2 | PN | Pre Nursery |
| 3 | N | Nursery |
| 4 | P | Primary |
| 5 | J | Junior Secondary |
| 6 | S | Senior Secondary |
| 7 | UP | Upper Primary |

**Current class structure:**
- Nursery, LKG, UKG ? type: Nursery (N)
- Class 1-4 ? type: Primary (P)
- Class 5-8 ? type: Upper Primary (UP)

**Section model:** Each class has sections (e.g., Gold, Diamond). Sections have a `teacher_id` = homeroom teacher.

**Subject model:** Each subject belongs to a class (`my_class_id`) and has a `teacher_id` (the subject teacher).

**Controllers:** `MyClassController`, `SectionController`, `SubjectController`

---

### 5.3 Academics - Exams & Marks

**Purpose:** Record and calculate student academic performance.

**Database tables:** `exams`, `marks`, `exam_records`, `grades`

**Exam structure:**
- `exams`: name, term (1 or 2 only — 2 semesters), year
- `marks`: per-student per-subject scores (t1=Assessment, t2=Mid Exam, exm=Final Exam, tca=t1+t2, tex1/tex2=term totals)
- `exam_records`: per-student per-exam summary (total, ave, class_ave, pos)

**Mark entry workflow:**
1. Teacher selects exam, class, section, subject via selector form
2. `MarkController::selector()` creates mark and exam_record rows for all students
3. Teacher enters scores on manage page (Assessment max 30, Mid Exam max 20, Final Exam max 50)
4. On save: `tca = t1 + t2`, `tex{term} = tca + exm`, grade looked up, subject position calculated
5. Exam record updated: total (sum all subjects), average, class average, position

**Grading system (Ethiopian, age-appropriate):**

| Class Type | Grade Style | Scale |
|---|---|---|
| Nursery/KG (C, PN, N) | Descriptive | Excellent(80-100), Good(60-79), Satisfactory(40-59), Needs Improvement(0-39) |
| Primary Class 1-4 (P) | Letter grades | A+(90-100), A(80-89), B(70-79), C(60-69), D(50-59), F(0-49) |
| Upper Primary Class 5-8 (UP) | Letter grades (stricter) | A+(90-100), A(75-89), B(60-74), C(50-59), D(40-49), F(0-39) |

**Batch Fix:** Recalculates grades and exam record totals/averages for an entire class. Bug fixed: was passing Grade object instead of `grade->id` to database.

**Mark sheet differences by class level:**
- Nursery: no GRADE column shown, remarks show descriptive name ("Excellent")
- Primary/Upper Primary: GRADE column shown with letter grade
- Psychomotor/Affective Traits: hidden for all (Nigerian curriculum feature, not used)

---

### 5.4 Academics - Timetable

**Purpose:** Schedule subjects into time periods for each class.

**Database tables:** `time_table_records`, `time_tables`, `time_slots`

**Structure:**
- `TimeTableRecord` = header (class, year, optional exam)
- `TimeSlot` = time period definition (e.g., "8:00 AM - 9:00 AM")
- `TimeTable` = individual entry (which subject in which slot on which day)

**Note:** Teacher is NOT stored on TimeTable — it comes from `subjects.teacher_id`.

**Conflict detection (TimetableValidationService):**
1. `class_double_booked` - two subjects in same slot+day for same class
2. `teacher_double_booked` - same teacher in two classes at same time (checks across all timetables in the year)
3. `subject_not_scheduled` - class subject has no timetable entry
4. `subject_repeated_same_day` - same subject appears twice in one day

Each conflict includes a suggested fix (names available teachers, suggests free slots).

---

### 5.5 Academics - Attendance

**Purpose:** Track daily student attendance per class.

**Database tables:** `attendance_sessions`, `attendance_records`

**Structure:**
- `AttendanceSession`: class, section, teacher, date, year
- `AttendanceRecord`: session_id, student_id, status (present/absent/late)

**Homeroom restriction:**
- Only the teacher assigned to a section (`sections.teacher_id`) can mark attendance for that section
- Admins can VIEW all sessions but CANNOT create or save attendance
- Route-level enforcement: `attendance.create`, `attendance.manage`, `attendance.store` use `teacher` middleware

**Dropout Early Warning (AttendanceRiskService):**
Risk score from 5 factors (max 100):
- Attendance < 65%: 30 pts
- Attendance 65-74% (below MoE 75% minimum): 15 pts
- Attendance declining > 10 percentage points: 20 pts
- Academic average < 50%: 25 pts
- Grades declining > 15 points: 15 pts
- 5+ consecutive absences: 10 pts

Risk levels: Critical (50+), Warning (25-49), Low (0-24)

---

### 5.6 Communication

**Purpose:** Internal messaging and school-wide announcements.

**Database tables:** `messages`, `announcements`

**Announcements:** Created by admin/super_admin, targeted by audience (all/students/teachers/parents).

**Messaging rules by role:**
- Admin/Super Admin: can message anyone
- Teacher: can message parents of their students only
- Parent: can message teachers of their children only
- Others: can message admins only

**Message read tracking:** `messages.read` boolean, set to true when receiver opens the message.

**AI Summarization:** Messages > 200 characters show a "Summarize with AI" button. Sends body to `POST /ai/summarize-message`, returns 1-2 sentence summary from TinyLlama.

---

### 5.7 Library

**Purpose:** Manage school book catalog and student borrowing.

**Database tables:** `books`, `book_requests`

**Book fields:** name, author, description, total_copies, issued_copies, my_class_id (optional class restriction)

**Borrow workflow:**
1. Student/parent requests book via `library.request`
2. Admin approves: status ? "approved", `issued_copies` incremented
3. Admin returns: status ? "returned", `issued_copies` decremented
4. Overdue = approved + issued_at > 14 days ago

**RulesEngine validations:**
- Max 3 books per user at once
- No duplicate active requests for same book
- Cannot return a book that was never issued

---

### 5.8 HR & Finance

**Purpose:** Staff management and school fee collection. Restricted to `hr_manager` role only.

**HR Database tables:** `staff_records`, `departments`, `staff_attendances`

**Finance Database tables:** `payments`, `payment_records`, `receipts`

**Payment workflow:**
1. HR Manager creates payment type (e.g., "Term 1 Fee", amount, class)
2. `PaymentController::select_class()` auto-creates payment_records for all students in a class
3. HR Manager records payment: `amt_paid` updated, receipt created
4. `paid=1` when balance reaches zero

**Payment methods supported:** Cash, Bank Transfer, Chapa (online)

**Chapa integration (sandbox):**
- Initiates payment via `POST /api/v1/transaction/initialize`
- Redirects student/parent to Chapa checkout
- Callback at `POST /chapa/callback` verifies and applies payment
- Requires `CHAPA_SECRET_KEY` in `.env`

---

### 5.9 Reports

**Purpose:** Generate analytical reports for decision-making.

**Available reports:**
| Report | Data | Access |
|---|---|---|
| Students | Per-class counts, gender breakdown, promotions | teamSAT |
| Attendance | Per-class attendance %, session counts | teamSAT |
| Academic | Class averages, top 10 students | teamSAT |
| Finance | Fees collected, outstanding, per-class | hr_manager only |
| Library | Most borrowed, overdue, history | teamSAT |

**Export formats:** PDF (DomPDF) and CSV (PHP stream)

**Filtering:** All reports support class filter; year filter uses current session by default.

---

### 5.10 Settings & Rules Engine

**Purpose:** Configurable system behaviour without code changes.

**Database table:** `rules`

**Rule fields:** name, type, condition (lt/lte/gt/gte/eq), value, action, active, description

**Current rule types:**
- `attendance_block` - block results if attendance below threshold
- `fee_block` - block results if unpaid fees exist
- `class_capacity` - max students per section (default 40)
- `mark_weight` - assessment_max(30), mid_exam_max(20), final_exam_max(50)

**RulesEngine service methods:**
- `validatePromotion()` - class progression enforcement
- `validateAttendanceSession()` - future dates, duplicates, homeroom check
- `validateBookBorrow()` - availability, max 3 books, no duplicates
- `validatePayment()` - positive amount, not exceeding balance
- `validateAdmissionNumber()` - uniqueness check
- `validateClassCapacity()` - section enrollment limit
- `validateTimetableConflict()` - teacher/class double-booking
- `validateExamSession()` - past session guard

---

### 5.11 Parent Portal

**Purpose:** Read-only view of a child's school data for parents/guardians.

**Routes:** `/parent/dashboard`, `/parent/child/{id}`, `/parent/child/{id}/timeline`

**Dashboard shows:** All linked children with attendance %, latest exam result, fee status, borrowed books.

**Child detail shows:** Full attendance history, exam results (blocked if rules triggered), fee breakdown with Chapa payment button, library borrows, teacher messages.

**Timeline:** Chronological feed of all events (attendance, exams, payments, library, announcements).

**Security:** `my_parent` middleware; `MyController` verifies `my_parent_id` matches Auth user before showing any child data.

---

## 6. AI FEATURES DEEP DIVE

### 6.1 AI Report Card Comment Generation

**Problem it solves:** Teachers spend significant time writing individualised report card comments. Generic comments don't reflect actual student performance patterns.

**Technical flow:**
1. Teacher enters marks on the Enter Marks page
2. Clicks the stars button (bi-stars icon) next to a student
3. JavaScript reads t1, t2, exm values from the same table row
4. AJAX POST to `/ai/generate-comment` with student_name, subject, assessment, mid_exam, final_exam
5. `AICommentController::generate()` validates inputs, calls `AICommentService::generateComment()`
6. Service detects pattern, builds structured prompt, calls Ollama
7. Comment returned and inserted into the textarea

**Pattern detection logic (7 patterns):**
```
courseworkRatio = (assessment + midExam) / 50
examRatio = finalExam / 50

if courseworkRatio >= 0.7 AND examRatio < 0.5  ? strong_coursework_weak_exam
if examRatio >= 0.7 AND courseworkRatio < 0.5  ? strong_exam_weak_coursework
if total < 45                                   ? significant_struggle
if total >= 85                                  ? excellence
if previousTotal - total > 15                  ? significant_drop
if total - previousTotal > 15                  ? significant_improvement
else                                            ? consistent
```

**Prompt structure:**
```
STUDENT DATA: name, subject, scores, total, performance level
EVIDENCE-BASED OBSERVATION: [pattern-specific evidence text]
RECOMMENDED FOCUS AREA: [pattern-specific focus]
Instructions: Write 2-3 sentences, use student name, warm and professional
```

**Fallback:** If Ollama unreachable, returns hardcoded comment based on performance level.

---

### 6.2 AI Message Summarization

**Problem it solves:** Long parent messages are time-consuming for teachers to read during busy periods.

**Technical flow:**
1. Message read view checks `strlen($message->body) > 200`
2. If true, "Summarize with AI" button rendered
3. Click sends message body to `POST /ai/summarize-message`
4. `AICommentService::summarizeMessage()` sends to Ollama with temperature 0.3
5. Summary slides in below message body

**Prompt:** "Summarize the following parent message in 1-2 short sentences. Be concise and professional."

---

### 6.3 Smart Grade Analysis Dashboard

**Problem it solves:** Administrators have no quick way to identify which classes or students need academic intervention.

**Technical implementation (pure PHP, no AI API):**
- `PerformanceAnalysisService` queries `ExamRecord` for current and previous exam
- Current exam = most recent term in current session
- Previous exam = prior term, or last term of previous year
- At-risk = average < 50% OR dropped > 15% vs previous
- Subject alerts = class average < 50% OR declining trend

**No Ollama required** — algorithmic analysis only.

---

### 6.4 Attendance Risk Prediction (Early Warning System)

**Problem it solves:** Identifies students at risk of dropping out before end-of-term reports.

**Algorithm:**
```
score = 0
if attendance < 65%:          score += 30
elif attendance < 75%:        score += 15
if attendance declining >10pp: score += 20
if academic avg < 50%:        score += 25
if grades declining >15pts:   score += 15
if consecutive_absences >= 5: score += 10
score = min(100, score)

Critical: score >= 50
Warning:  score >= 25
Low:      score < 25
```

Aligned with Ethiopian Ministry of Education 75% minimum attendance requirement.

---

### 6.5 Timetable Conflict Detector

**Problem it solves:** Manual timetable creation often results in scheduling conflicts that are only discovered when classes start.

**4 conflict types detected:**
1. **class_double_booked** - two subjects in same slot+day for same class
2. **teacher_double_booked** - same teacher (via subjects.teacher_id) in two classes simultaneously, checked across ALL timetables in the year
3. **subject_not_scheduled** - a class subject has no timetable entry at all
4. **subject_repeated_same_day** - same subject appears twice on the same day

**Smart suggestions:** For teacher clashes, queries which teachers are actually free at that slot and names them. For double-booked slots, finds genuinely empty slots and suggests them by name.

---

### 6.6 Document OCR for Admission

**Problem it solves:** Manually typing student information from birth certificates is slow and error-prone.

**Technical implementation:**
- Tesseract.js 5 loaded lazily from CDN (only when user clicks "Scan")
- Runs entirely in the browser — no server processing, no data sent externally
- Extracts: Full name (2-3 capitalised words), Date of birth (multiple formats), Address/place

**Extraction patterns:**
```javascript
Name:    /\b([A-Z][a-z]{1,20}\s+[A-Z][a-z]{1,20}(?:\s+[A-Z][a-z]{1,20})?)\b/
DOB:     /\b(\d{2}[\/\-\.]\d{2}[\/\-\.]\d{4})\b/  (DD/MM/YYYY)
         /\b(\d{4}[\/\-\.]\d{2}[\/\-\.]\d{2})\b/  (YYYY-MM-DD)
Address: /(?:Addis\s+Ababa|Addis\s+Abeba)/i
         /(?:Kebele|Woreda|Sub-?city)[:\s]+([^\n\r]{3,40})/i
```

**Auto-fill:** Populates `name`, `dob`, `address` fields. Teacher reviews before saving.

**Note on Amharic:** Change `'eng'` to `'eng+amh'` in Tesseract.recognize() to enable Amharic OCR (downloads ~15MB language pack on first use).

---

## 7. ETHIOPIAN LOCALIZATIONS

Complete list of changes made from the original Nigerian-focused codebase:

| Feature | Original | Ethiopian Version |
|---|---|---|
| Regions | 37 Nigerian states | 13 Ethiopian regions |
| Sub-divisions | 774 Nigerian LGAs | Addis Ababa 11 sub-cities + regional zones |
| Phone format | Generic string | `^09[0-9]{8}$` (Ethiopian mobile) |
| Mark weights | CA1(20) + CA2(20) + Exam(60) | Assessment(30) + Mid Exam(20) + Final(50) |
| Academic calendar | 3 terms | 2 semesters |
| Grading | Single letter scale | Age-appropriate: descriptive for Nursery, letters for Primary/Upper Primary |
| Class field | Sports House (text) | Religion (dropdown: Orthodox/Muslim/Protestant/Catholic/Traditional/Other) |
| Admission number | Manual text | Auto-generated: STM-YYYY-XXXX |
| Nationality default | None | Ethiopian pre-selected |
| Currency | ? (Naira) | ETB (Ethiopian Birr) |
| Payment gateway | None | Chapa (Ethiopian fintech) |
| Psychomotor/Affective traits | Shown | Hidden (Nigerian curriculum feature) |
| Grade types | Single scale | Nursery descriptive / Primary letter / Upper Primary stricter letter |
| Class progression | JSS/SSS | Nursery ? LKG ? UKG ? Class 1-8 |

---

## 8. DATABASE SCHEMA

### Core Tables

| Table | Purpose | Key Fields |
|---|---|---|
| `users` | All system users | id, name, username, email, user_type, photo, phone, phone2 |
| `user_types` | Role definitions | id, title, name, level |
| `student_records` | Student-specific data | user_id, my_class_id, section_id, adm_no, my_parent_id, religion, grad |
| `staff_records` | Staff employment data | user_id, emp_date, department_id |
| `my_classes` | School classes | id, name, class_type_id |
| `class_types` | Class level types | id, name, code (C/PN/N/P/UP/J/S) |
| `sections` | Class sections | id, name, my_class_id, teacher_id (homeroom) |
| `subjects` | Subjects per class | id, name, my_class_id, teacher_id |
| `exams` | Exam definitions | id, name, term (1 or 2), year |
| `marks` | Per-student per-subject scores | student_id, subject_id, exam_id, t1, t2, tca, exm, tex1, tex2, grade_id |
| `exam_records` | Per-student per-exam summary | student_id, exam_id, total, ave, class_ave, pos |
| `grades` | Grade scale definitions | id, name, class_type_id, mark_from, mark_to, remark |
| `payments` | Fee type definitions | id, title, amount, year, my_class_id |
| `payment_records` | Per-student payment tracking | student_id, payment_id, amt_paid, paid, balance |
| `receipts` | Individual payment receipts | pr_id, amt_paid, balance, payment_method, transaction_ref |
| `attendance_sessions` | Attendance session header | my_class_id, section_id, teacher_id, date, year |
| `attendance_records` | Per-student attendance | session_id, student_id, status (present/absent/late) |
| `books` | Library catalog | id, name, author, total_copies, issued_copies |
| `book_requests` | Borrow requests | book_id, user_id, status, issued_at, returned_at |
| `promotions` | Promotion history | student_id, from_class_id, to_class_id, from_session, status |
| `rules` | Rules engine entries | name, type, condition, value, action, active |
| `announcements` | School announcements | author_id, title, body, audience, active |
| `messages` | Internal messages | sender_id, receiver_id, subject, body, read |
| `audit_logs` | System activity log | user_id, action, module, description, ip_address |
| `departments` | HR departments | id, name, description |
| `staff_attendances` | Staff attendance | user_id, date, status, remark |
| `states` | Ethiopian regions | id, name |
| `lgas` | Sub-cities/woredas | id, state_id, name |

### Key Relationships
- `student_records.user_id` ? `users.id`
- `student_records.my_class_id` ? `my_classes.id`
- `student_records.section_id` ? `sections.id`
- `student_records.my_parent_id` ? `users.id`
- `sections.teacher_id` ? `users.id` (homeroom teacher)
- `subjects.teacher_id` ? `users.id` (subject teacher)
- `marks.student_id` ? `users.id`
- `marks.grade_id` ? `grades.id`
- `attendance_records.session_id` ? `attendance_sessions.id`
- `payment_records.student_id` ? `users.id`
- `receipts.pr_id` ? `payment_records.id`

---

## 9. SECURITY IMPLEMENTATION

### Authentication
- Laravel built-in Auth with username OR email login
- `LoginController::username()` detects email vs username dynamically
- Student accounts blocked from logging in (`LoginController::authenticated()`)
- Session-based authentication (not JWT)

### Route Protection
- All routes inside `middleware('auth')` group
- Role-specific routes use custom middleware aliases
- Finance/HR routes: `hr_manager` middleware (hr_manager only — admin excluded)
- Attendance write routes: `teacher` middleware (teachers only)
- Super admin routes: `super_admin` middleware

### Password Requirements
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 number
- Enforced via `regex:/^(?=.*[A-Z])(?=.*\d).+$/` in UserRequest

### Data Protection
- All IDs in URLs are obfuscated using Hashids (`hashids/hashids`)
- CSRF protection on all POST/PUT/DELETE forms (`VerifyCsrfToken` middleware)
- SQL injection prevented by Eloquent ORM (parameterised queries)
- XSS prevented by Blade's `{{ }}` auto-escaping
- File uploads validated: type (jpeg/png), size (max 2MB)

### Audit Logging
- `AuditLog::log(action, module, description)` called on all key operations
- Logs: student creation, exam creation, attendance saves, library approvals, payments
- Viewable at `/super_admin/audit-logs` (super_admin only)

---

## 10. KNOWN ISSUES / LIMITATIONS

| Issue | Status | Notes |
|---|---|---|
| Duplicate `home` route name | Known | `/` and `/home` both named `home` — prevents route caching. App works without cache. |
| Amharic OCR | Partial | Tesseract.js supports Amharic but requires manual language pack download |
| Chapa payment | Sandbox only | Requires real API keys for production |
| Staff record for hr_manager | Minor | HR Manager users may not always have a staff_record row |
| Timetable teacher detection | Limitation | Teacher comes from subjects.teacher_id — if subject has no teacher, conflict detection skips it |
| Mobile sidebar | Partial | Slide-in works but overlay tap-to-close may need testing on iOS |
| Batch Fix on empty class | Works | Only fails if grade scale missing for class type |

---

## 11. TESTING CHECKLIST

### Critical Paths
- [ ] Login with each role (emnet, admin, teacher, hr, parent)
- [ ] Admit a new student (all fields, auto-generated admission number)
- [ ] Enter marks for a class, verify totals calculate correctly
- [ ] Mark attendance as teacher (homeroom only)
- [ ] Verify admin CANNOT mark attendance
- [ ] Verify hr_manager CANNOT access students or marks
- [ ] Generate a report card PDF
- [ ] Create a payment, record payment, download receipt
- [ ] Send a message, read it from recipient account
- [ ] Run Batch Fix on a class with existing marks
- [ ] Validate a timetable with an intentional conflict
- [ ] View Early Warning dashboard

### User Acceptance Testing
- Parent logs in, views child's attendance and exam results
- Teacher enters marks, generates AI comment, saves
- HR Manager marks staff attendance, views finance report
- Admin views Smart Insights dashboard
- Super Admin changes system settings, views audit logs

---

## 12. DEPLOYMENT NOTES

### Server Requirements
- PHP 8.0+ with extensions: pdo_mysql, mbstring, fileinfo, gd, curl, zip, openssl, intl
- MySQL 8.0+
- Composer 2.x
- 1GB RAM minimum
- 10GB disk space (for uploads and logs)

### Environment Variables Required
```env
APP_NAME="St. Mark SMS"
APP_ENV=production
APP_KEY=           # generated by php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stmarksms
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

OLLAMA_MODEL=tinyllama
OLLAMA_URL=http://127.0.0.1:11434

CHAPA_SECRET_KEY=   # from dashboard.chapa.co
CHAPA_PUBLIC_KEY=   # from dashboard.chapa.co
```

### Post-Deployment Steps
```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
# Do NOT run route:cache (duplicate home route name)
```

### First Login After Deployment
1. Log in as `emnet` (super_admin)
2. Go to Settings ? update school name, logo, current session
3. Change all default passwords
4. Assign homeroom teachers to sections (Academics ? Sections ? Edit)
5. Assign teachers to subjects (Academics ? Subjects ? Edit)
6. Create exams for the current semester
