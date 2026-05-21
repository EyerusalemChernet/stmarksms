# Implementation Plan: Student Promotion Engine

## Overview

Implement the Student Promotion Engine as a Laravel 8 feature on top of the existing SMS codebase. The engine is additive — it never modifies existing controllers, models, or tables (except adding `subject_category` to `subjects`). All new code lives in dedicated namespaces, migrations, and view directories. Implementation follows the seven logical groups below, each building on the previous.

**Stack:** Laravel 8, PHP 8.x  
**Key namespaces:** `App\Http\Controllers\SuperAdmin`, `App\Services`, `App\Models`  
**Test directories:** `tests/Unit/`, `tests/Feature/`

---

## Tasks

- [ ] 1. Database migrations
  - [ ] 1.1 Add `subject_category` column to `subjects` table
    - Create migration: `add_subject_category_to_subjects_table`
    - Add `subject_category ENUM('core','elective','optional') NOT NULL DEFAULT 'elective'` after `master_subject_id`
    - Add index on `subject_category` for scope queries
    - _Requirements: 1.1, 1.2_

  - [ ] 1.2 Create `class_progressions` migration
    - Columns: `id`, `from_class_id` (FK → `my_classes`), `to_class_id` (FK → `my_classes`), `created_at`, `updated_at`
    - Add `UNIQUE KEY uq_from_class (from_class_id)` to enforce one successor per class
    - Add index on `to_class_id`
    - _Requirements: 2.1, 2.6_

  - [ ] 1.3 Create `promotion_rules` migration
    - Columns: `id`, `name`, `rule_type` (ENUM of 7 types), `condition_operator` (ENUM nullable), `threshold_value` (DECIMAL nullable), `scope_type` (ENUM), `scope_class_id` (FK nullable), `scope_department_id` (FK nullable), `scope_year`, `is_active` (default 1), `description`, `created_by` (FK → `users`), timestamps
    - Add composite index `idx_active_scope (is_active, scope_type)`
    - _Requirements: 3.1, 3.2, 3.3_

  - [ ] 1.4 Create `promotion_runs` migration
    - Columns: `id`, `academic_year`, `scope_class_id` (FK nullable), `status` (ENUM: pending/previewing/finalized/cancelled), `initiated_by` (FK), `finalized_by` (FK nullable), `initiated_at`, `finalized_at`, `student_count`, `notes`, timestamps
    - Add composite index `idx_year_status (academic_year, status)`
    - Add `is_archived TINYINT(1) DEFAULT 0` for future archiving (Phase 1 lifecycle)
    - _Requirements: 4.1, 5.1_

  - [ ] 1.5 Create `promotion_previews` migration
    - Columns: `id`, `run_id` (FK → `promotion_runs`), `student_id` (FK → `users`), `current_class_id`, `current_section_id`, `target_class_id` (nullable), `target_section_id` (nullable), `yearly_average`, `failed_subjects`, `attendance_rate`, `fee_cleared`, `computed_status` (ENUM), `override_status` (ENUM nullable), `override_by` (FK nullable), `override_reason`, `overridden_at`, `failed_rules` (JSON), timestamps
    - Add indexes: `idx_run_status (run_id, computed_status)`, `idx_run_student (run_id, student_id)`
    - _Requirements: 6.1, 6.2_

  - [ ] 1.6 Create `promotion_logs` migration
    - Columns: `id`, `run_id` (FK), `student_id` (FK), `previous_class_id` (FK), `new_class_id` (FK), `previous_section_id`, `new_section_id`, `academic_year`, `promotion_status` (ENUM), `promoted_by` (FK), `promotion_date`, `is_override`, `override_reason`, `notes`, `created_at` only (no `updated_at` — append-only)
    - Add `is_archived TINYINT(1) DEFAULT 0` (never deleted, but flagged for future archiving)
    - Add indexes: `idx_student_year (student_id, academic_year)`, `idx_run (run_id)`, `idx_status (promotion_status)`
    - _Requirements: 7.1, 7.2_

  - [ ]* 1.7 Write migration smoke test
    - Verify all six new tables exist with expected columns after running migrations
    - Verify `subjects` table has `subject_category` column
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 6.1, 7.1_

- [ ] 2. Eloquent models
  - [ ] 2.1 Update `App\Models\Subject` model
    - Add `subject_category` to `$fillable`
    - Add `isCoreSubject(): bool` method returning `$this->subject_category === 'core'`
    - Add `scopeCore(Builder $q)` query scope filtering by `subject_category = 'core'`
    - _Requirements: 1.2, 1.4, 1.5_

  - [ ]* 2.2 Write property test for `isCoreSubject()` equivalence
    - **Property 1: Subject category validation rejects invalid values**
    - **Validates: Requirements 1.1**
    - **Property 2: `isCoreSubject()` is equivalent to category equality**
    - **Validates: Requirements 1.5**
    - Use eris generators to test all three valid values and random invalid strings

  - [ ] 2.3 Create `App\Models\ClassProgression` model
    - `$fillable`: `from_class_id`, `to_class_id`
    - Relations: `fromClass()` → `BelongsTo(MyClass)`, `toClass()` → `BelongsTo(MyClass)`
    - Validation rule method `validate(array $data)` that rejects same-class pairs and non-existent class IDs
    - _Requirements: 2.1, 2.2_

  - [ ]* 2.4 Write property test for `ClassProgression` validation
    - **Property 3: Class progression validation rejects same-class mappings**
    - **Validates: Requirements 2.2**
    - Generate random ID pairs; assert same-ID pairs are always rejected

  - [ ] 2.5 Create `App\Models\PromotionRule` model
    - `$fillable`: all columns except `id`, timestamps
    - Query scopes: `scopeActive()`, `scopeForClass(int $classId)`, `scopeForDepartment(int $deptId)`
    - Relations: `scopeClass()` → `MyClass`, `scopeDepartment()` → `Department`, `createdBy()` → `User`
    - _Requirements: 3.1, 3.5, 3.6_

  - [ ] 2.6 Create `App\Models\PromotionRun` model
    - `$fillable`: all columns except `id`, timestamps
    - Relations: `previews()` → `hasMany(PromotionPreview)`, `logs()` → `hasMany(PromotionLog)`, `initiatedBy()` → `User`, `finalizedBy()` → `User`, `scopeClass()` → `MyClass`
    - _Requirements: 4.1, 7.1_

  - [ ] 2.7 Create `App\Models\PromotionPreview` model
    - `$fillable`: all columns except `id`, timestamps
    - `$casts`: `failed_rules` → `array`
    - Relations: `run()`, `student()`, `currentClass()`, `targetClass()`, `overrideBy()`
    - Accessor `getEffectiveStatusAttribute()`: returns `override_status ?? computed_status`
    - _Requirements: 6.1, 6.3_

  - [ ] 2.8 Create `App\Models\PromotionLog` model
    - `$fillable`: all columns except `id`
    - Set `public $timestamps = false` (only `created_at` exists)
    - Override `save()` to throw `\RuntimeException` if the model already exists (append-only guard)
    - Relations: `run()`, `student()`, `previousClass()`, `newClass()`, `promotedBy()`
    - _Requirements: 7.1, 7.2_

  - [ ]* 2.9 Write unit tests for model methods
    - Test `Subject::isCoreSubject()` for all three category values
    - Test `PromotionLog::save()` throws on update attempt
    - Test `PromotionPreview::getEffectiveStatusAttribute()` returns override when set, computed when not
    - _Requirements: 1.5, 7.2_

- [ ] 3. Checkpoint — run migrations and verify models load
  - Run `php artisan migrate` and confirm all tables are created without errors
  - Ensure all new models can be instantiated without errors
  - Ask the user if any questions arise before proceeding to the service layer.

- [ ] 4. `PromotionEngineService` — data gathering helpers
  - [ ] 4.1 Create `App\Services\PromotionEngineService` with constructor injection
    - Inject `App\Services\RulesEngine` for fallback class resolution
    - Read `custom_pass_mark`, `calculation_basis`, `weighted_average`, `promotion_mode` from `settings` table in constructor
    - _Requirements: 9.1, 9.3, 9.4, 9.5_

  - [ ] 4.2 Implement `computeYearlyAverage(int $studentId, string $academicYear): ?float`
    - Query `exam_records` joined with `exams` filtered by `year = $academicYear`
    - Return `round(sum(ave) / count, 2)` or `null` if no records
    - Respect `calculation_basis` setting (term vs semester)
    - _Requirements: 4.2, 9.3_

  - [ ]* 4.3 Write property test for `computeYearlyAverage()`
    - **Property 8: Yearly average equals mean of exam_record averages**
    - **Validates: Requirements 4.2**
    - Generate random collections of N exam records; assert result equals `sum/N` rounded to 2dp; assert null when N=0

  - [ ] 4.4 Implement `computeAttendanceRate(int $studentId, string $academicYear): float`
    - Join `attendance_records` with `attendance_sessions` filtered by `year`
    - Count present/late as attended; return percentage (0.0–100.0)
    - _Requirements: 4.6_

  - [ ] 4.5 Implement `hasClearedFees(int $studentId): bool`
    - Query `payment_records` for any unpaid rows for the student
    - Return `true` if no unpaid records exist
    - _Requirements: 4.7_

  - [ ] 4.6 Implement `countFailedSubjects(int $studentId, string $academicYear, int $passMark): int`
    - Query `marks` for the student and year where `cum_ave < $passMark`
    - Return count of failing subjects
    - _Requirements: 4.5_

  - [ ] 4.7 Implement `checkCoreSubjects(int $studentId, string $academicYear, float $threshold): array`
    - Join `marks` with `subjects` where `subject_category = 'core'`
    - Return `['passed' => bool, 'failures' => [...]]` with per-subject details
    - _Requirements: 4.4_

- [ ] 5. `PromotionEngineService` — rule resolution and evaluation
  - [ ] 5.1 Implement `getApplicableRules(int $classId, ?int $departmentId, string $academicYear): Collection`
    - Load all active rules matching the student's class, department, year, or school scope
    - Group by `rule_type`; for each type keep only the highest-priority (most specific) rule
    - Priority order: class > department > year > school
    - _Requirements: 3.7, 4.1_

  - [ ]* 5.2 Write property test for `getApplicableRules()` scope priority
    - **Property 6: Deactivated rules are excluded from evaluation**
    - **Validates: Requirements 3.5**
    - **Property 7: More specific scope overrides broader scope**
    - **Validates: Requirements 3.7**
    - Generate random rule sets with mixed active/inactive and overlapping scopes

  - [ ] 5.3 Implement private `evaluateRule(PromotionRule $rule, array $studentData): array`
    - Dispatch to per-type handler based on `rule_type`
    - Return `['passed' => bool, 'rule_id' => int, 'rule_name' => string, 'rule_type' => string, 'threshold' => mixed, 'actual' => mixed, 'is_conditional' => bool]`
    - Handle all 7 rule types including `discipline_restriction` (always passes for now)
    - _Requirements: 3.1, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [ ] 5.4 Implement `determineStatus(array $ruleResults, array $studentData): string`
    - Return `'Promoted'` if no failures
    - Return `'Conditionally Promoted'` if all failures are conditional
    - Return `'Pending Review'` if `exam_count < expected_exam_count`
    - Return `'Repeated'` otherwise
    - _Requirements: 4.8, 4.9, 4.10, 4.11_

  - [ ]* 5.5 Write property tests for `determineStatus()`
    - **Property 9: Passing all rules yields Promoted status**
    - **Validates: Requirements 4.8**
    - **Property 10: Failing only conditional rules yields Conditionally Promoted**
    - **Validates: Requirements 4.9**
    - **Property 11: Failing any non-conditional rule yields Repeated**
    - **Validates: Requirements 4.10**
    - **Property 12: Incomplete marks yield Pending Review**
    - **Validates: Requirements 4.11**

  - [ ] 5.6 Implement `resolveNextClass(int $fromClassId, string $status): array`
    - If `$status === 'Repeated'`, return current class and section unchanged
    - Look up `ClassProgression` by `from_class_id`; if found, use `to_class_id`
    - Fallback to `RulesEngine::getNextClassInOrder()` if no mapping exists
    - Return `null` (triggers Pending Review) if no class found
    - _Requirements: 2.4, 2.5, 5.3, 5.4_

  - [ ]* 5.7 Write property tests for `resolveNextClass()`
    - **Property 4: Next-class resolution respects DB mapping over fallback**
    - **Validates: Requirements 2.4**
    - **Property 5: Missing progression path yields Pending Review**
    - **Validates: Requirements 2.5**

- [ ] 6. `PromotionEngineService` — run orchestration
  - [ ] 6.1 Implement pre-flight validation in `runPromotion()`
    - Verify academic year exists in `exams` and all exams have `status = 'finalized'`
    - Abort with descriptive error message if not finalized
    - Verify at least one active student exists for the scope
    - _Requirements: 5.1, 5.2_

  - [ ] 6.2 Implement student iteration loop in `runPromotion()`
    - Load active `StudentRecord` rows for the scope (filter by `is_active`, `session`, non-graduated)
    - For each student: gather data, get applicable rules, evaluate rules, determine status, resolve next class
    - Create `PromotionPreview` records in a DB transaction
    - Update `PromotionRun.status` to `'previewing'` and set `student_count`
    - All queries on active data must filter by `is_active` and `academic_year` for performance
    - _Requirements: 4.1, 4.12, 5.5, 5.6_

  - [ ]* 6.3 Write property test for non-destructive runs
    - **Property 13: Promotion runs are non-destructive**
    - **Validates: Requirements 5.5**
    - Assert row counts in `student_records`, `marks`, `exam_records` are unchanged after a run

  - [ ] 6.4 Implement `applyOverride(int $previewId, string $status, string $notes, int $adminId): PromotionPreview`
    - Reject if run is already finalized (return 422)
    - Require non-empty notes when overriding a `Pending Review` student
    - Update `override_status`, `override_by`, `override_reason`, `overridden_at`
    - _Requirements: 6.3, 6.4, 8.4_

  - [ ]* 6.5 Write property test for override log completeness
    - **Property 15: Override is recorded in the log with actor and reason**
    - **Validates: Requirements 6.4**
    - Generate random admin IDs and reason strings; assert all fields are persisted

  - [ ] 6.6 Implement `bulkOverride(array $previewIds, string $status, string $notes, int $adminId): int`
    - Apply the same override to all selected preview IDs in a single transaction
    - Return count of updated records
    - _Requirements: 8.1, 8.2_

  - [ ]* 6.7 Write property test for bulk override completeness
    - **Property 18: Bulk override updates all selected students**
    - **Validates: Requirements 8.2**
    - Generate random subsets of preview IDs; assert every selected record is updated

  - [ ] 6.8 Implement `finalizeRun(int $runId, int $adminId): PromotionRun`
    - Wrap entire finalization in a DB transaction
    - For each preview: determine effective status, create new `StudentRecord` for new session (never update old), create `PromotionLog` record
    - Update `PromotionRun.status` to `'finalized'`, set `finalized_by` and `finalized_at`
    - Roll back on any DB failure; revert run status to `'previewing'`
    - _Requirements: 5.5, 5.6, 7.1, 7.2, 7.3, 7.4_

  - [ ]* 6.9 Write property test for finalization creates new student_records rows
    - **Property 14: Finalization creates new student_records rows**
    - **Validates: Requirements 5.6**
    - Assert original `student_records` rows are untouched after finalization

  - [ ]* 6.10 Write property test for promotion log completeness and monotonicity
    - **Property 16: Finalized promotion logs contain all required fields**
    - **Validates: Requirements 7.1**
    - **Property 17: Promotion log count is monotonically non-decreasing**
    - **Validates: Requirements 7.2**

- [ ] 7. `PromotionEngineService` — configuration and export
  - [ ] 7.1 Implement `getApplicableSettings(): array` helper
    - Read `promotion_mode`, `custom_pass_mark`, `calculation_basis`, `weighted_average` from `settings` table
    - Return array with defaults: `manual`, `50`, `term`, `0`
    - _Requirements: 9.1, 9.3, 9.4, 9.5_

  - [ ] 7.2 Implement `exportRun(int $runId, string $format): string`
    - Support `'csv'` format: generate CSV with columns from Requirement 8.5
    - Support `'pdf'` format using Laravel's built-in response or a simple HTML-to-PDF approach
    - Return the file path or stream response
    - _Requirements: 8.5_

  - [ ]* 7.3 Write property test for `custom_pass_mark` validation
    - **Property 19: custom_pass_mark validation enforces [0, 100] range**
    - **Validates: Requirements 9.6**
    - Generate random integers; assert values outside [0, 100] are rejected

- [ ] 8. Checkpoint — unit test the service layer
  - Run `php artisan test tests/Unit/` and confirm all unit and property tests pass
  - Ask the user if any questions arise before proceeding to the controller.

- [ ] 9. `PromotionEngineController`
  - [ ] 9.1 Create `App\Http\Controllers\SuperAdmin\PromotionEngineController`
    - Extend base `Controller`; inject `PromotionEngineService` via constructor
    - Apply `super_admin` middleware in constructor
    - _Requirements: 10.1, 10.4_

  - [ ] 9.2 Implement `dashboard()` action
    - Accept optional `academic_year` query param; default to current session from `settings`
    - Load per-status counts and per-class breakdown from `promotion_previews` for the selected year
    - Pass data to `pages.super_admin.promotion_engine.dashboard` view
    - _Requirements: 10.1, 10.2, 10.3, 10.5_

  - [ ] 9.3 Implement `rules()`, `storeRule()`, `updateRule()`, `destroyRule()` actions
    - `rules()`: load all rules with pagination; pass to `promotion-engine.rules.index`
    - `storeRule()` / `updateRule()`: validate required fields (name, rule_type, scope_type); call service or direct model save; redirect with flash
    - `destroyRule()`: soft-delete or hard-delete; redirect with flash
    - _Requirements: 3.2, 3.4, 3.5_

  - [ ] 9.4 Implement `progressions()`, `storeProgression()`, `updateProgression()`, `destroyProgression()` actions
    - `progressions()`: load all progressions with class names; pass to `promotion-engine.progressions.index`
    - `storeProgression()` / `updateProgression()`: validate `from_class_id != to_class_id`, both exist; redirect with flash
    - `destroyProgression()`: delete and redirect
    - _Requirements: 2.2, 2.3_

  - [ ] 9.5 Implement `initiateRun()` action
    - Validate `academic_year` and optional `scope_class_id` from request
    - Call `PromotionEngineService::runPromotion()`; redirect to preview on success
    - Return flash error on pre-flight failure
    - _Requirements: 4.1, 5.1, 5.2_

  - [ ] 9.6 Implement `preview()` action
    - Load `PromotionRun` with paginated `PromotionPreview` records
    - Support filters: `status`, `class_id`, `search` (student name)
    - Pass summary counts to view
    - _Requirements: 6.1, 6.2, 6.5_

  - [ ] 9.7 Implement `applyOverride()` and `bulkOverride()` actions (JSON responses)
    - Both return JSON `{success: bool, message: string}`
    - Validate inputs; delegate to service; return 422 on validation failure
    - _Requirements: 6.3, 6.4, 8.1, 8.2_

  - [ ] 9.8 Implement `finalizeRun()` action
    - Call `PromotionEngineService::finalizeRun()`; redirect to history on success
    - Return flash error on failure
    - _Requirements: 6.6, 7.1_

  - [ ] 9.9 Implement `history()` action
    - Load `PromotionLog` records with filters: `student_name`, `class_id`, `academic_year`, `status`
    - Paginate results; pass to `promotion-engine.history` view
    - _Requirements: 7.5, 7.6_

  - [ ] 9.10 Implement `export()` action
    - Accept `format` query param (`csv` or `pdf`)
    - Call `PromotionEngineService::exportRun()`; return file download response
    - _Requirements: 8.5_

- [ ] 10. Blade views
  - [ ] 10.1 Create layout scaffold for promotion engine views
    - Create directory `resources/views/pages/super_admin/promotion_engine/`
    - Create `partials/rule_form.blade.php` — shared form fields for rule create/edit (name, rule_type, condition_operator, threshold_value, scope_type, scope selectors, description, is_active toggle)
    - Create `partials/student_row.blade.php` — single student row for preview table (name, class, avg, attendance, fee status, computed status badge, override button)
    - _Requirements: 3.4, 6.1_

  - [ ] 10.2 Create `dashboard.blade.php`
    - Year selector dropdown (all academic years from `exams`)
    - Summary stat cards: Total, Promoted (%), Conditionally Promoted (%), Repeated (%), Pending Review (%)
    - Per-class breakdown table
    - "Initiate New Promotion Run" form (year + optional class scope)
    - "View History" link
    - Empty state message when no run exists for selected year
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [ ] 10.3 Create `rules/index.blade.php`
    - Table listing all promotion rules with columns: name, type, scope, threshold, status (active/inactive), actions
    - "Add Rule" button opening create modal (uses `partials/rule_form.blade.php`)
    - Edit and delete actions per row
    - Active/inactive toggle button
    - _Requirements: 3.4, 3.5_

  - [ ] 10.4 Create `progressions/index.blade.php`
    - Table listing all class progressions: From Class → To Class, actions
    - "Add Progression" button opening create modal
    - Edit and delete actions per row
    - _Requirements: 2.3_

  - [ ] 10.5 Create `preview.blade.php`
    - Run header: run ID, academic year, status badge, student count
    - Filter bar: class dropdown, status dropdown, name search input
    - Bulk action bar: select-all checkbox, Approve / Reject / Repeat buttons
    - Student table using `partials/student_row.blade.php`; each row has override modal trigger
    - Override modal: status select, notes textarea (required for Pending Review)
    - Summary counts bar (Promoted N, Repeated N, etc.)
    - Export CSV button and Finalize Run button (disabled if already finalized)
    - _Requirements: 6.1, 6.2, 6.3, 6.5, 8.1, 8.3, 8.5_

  - [ ] 10.6 Create `history.blade.php`
    - Filter bar: student name search, class dropdown, academic year dropdown, status dropdown
    - Table: student name, previous class, new class, academic year, status badge, promoted-by, date, notes
    - Pagination
    - _Requirements: 7.5, 7.6_

  - [ ] 10.7 Add promotion engine settings section to existing settings page
    - Add fields for `promotion_mode`, `custom_pass_mark`, `calculation_basis`, `weighted_average` to the existing SuperAdmin settings view (or create a dedicated settings partial)
    - Validate `custom_pass_mark` as integer 0–100 client-side and server-side
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_

  - [ ]* 10.8 Write property test for dashboard status counts sum
    - **Property 20: Dashboard status counts sum to total**
    - **Validates: Requirements 10.1**
    - Generate random sets of preview records; assert per-status counts sum to total

- [ ] 11. Routes and menu integration
  - [ ] 11.1 Register promotion engine route group in `routes/web.php`
    - Add inside the existing `super_admin` middleware group
    - Use namespace `App\Http\Controllers\SuperAdmin`
    - Register all 16 routes as specified in the design (GET/POST/PUT/DELETE with named routes `promotion_engine.*`)
    - _Requirements: 10.4_

  - [ ] 11.2 Add "Promotion Engine" menu item to `resources/views/partials/menu.blade.php`
    - Add inside the `@if(Qs::userIsSuperAdmin())` block in the Students section
    - Use `route('promotion_engine.dashboard')` and active class detection via `str_starts_with(Route::currentRouteName(), 'promotion_engine.')`
    - Icon: `bi bi-diagram-3`
    - _Requirements: 10.4_

- [ ] 12. Feature (integration) tests
  - [ ]* 12.1 Write integration test for `POST /promotion-engine/run`
    - Seed a finalized academic year with students, rules, and class progressions
    - Assert `PromotionRun` and `PromotionPreview` records are created
    - Assert pre-flight rejects unfinalized year with error flash
    - _Requirements: 4.1, 5.1, 5.2_

  - [ ]* 12.2 Write integration test for override endpoints
    - Test `POST /promotion-engine/preview/{id}/override` updates preview and returns JSON success
    - Test override on finalized run returns 422
    - Test Pending Review override without notes returns 422
    - _Requirements: 6.3, 6.4, 8.4_

  - [ ]* 12.3 Write integration test for `POST /promotion-engine/preview/{id}/finalize`
    - Assert new `student_records` rows are created for the new session
    - Assert original `student_records` rows are unchanged
    - Assert `PromotionLog` records are created with all required fields
    - Assert run status becomes `'finalized'`
    - _Requirements: 5.5, 5.6, 7.1, 7.2_

  - [ ]* 12.4 Write integration test for `GET /promotion-engine/export/{id}`
    - Assert response is a CSV download with correct headers
    - Assert CSV contains one row per student in the run
    - _Requirements: 8.5_

- [ ] 13. Final checkpoint — full test suite
  - Run `php artisan test` and confirm all unit, property, and feature tests pass
  - Verify no existing tests are broken (especially `PromotionController` and `TermSetupController` tests)
  - Ask the user if any questions arise before considering the implementation complete.

---

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Checkpoints (tasks 3, 8, 13) ensure incremental validation at logical boundaries
- Property tests use the [eris](https://github.com/giorgiosironi/eris) library with a minimum of 100 iterations each
- The `promotion_logs` table is append-only by design — the `PromotionLog` model's `save()` override enforces this at the application layer
- All queries on active data must filter by `is_active` and `academic_year` columns; ensure indexes exist on these columns before running the engine on large datasets
- The existing `PromotionController` and `TermSetupController` must not be modified; the new engine is purely additive
