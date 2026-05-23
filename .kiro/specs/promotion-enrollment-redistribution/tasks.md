# Implementation Plan: Promotion, Enrollment, and Interactive Section Redistribution

## Overview

Implement the Promotion, Enrollment, and Interactive Section Redistribution module as a fully additive Laravel 8 feature. No existing tables, controllers, or routes are modified. All new code lives in dedicated namespaces, migrations, services, and view directories.

**Stack:** Laravel 8, PHP 8.x, Alpine.js, SortableJS (CDN), Bootstrap 4  
**Key namespaces:** `App\Http\Controllers\SupportTeam`, `App\Http\Controllers\SuperAdmin`, `App\Services`, `App\Models`

---

## Tasks

- [-] 1. Database Migrations

  - [x] 1.1 Create `academic_years` migration
    - Columns: `id`, `year_name` (VARCHAR 20, UNIQUE), `is_active` (TINYINT default 0), timestamps
    - Index on `is_active`
    - _Requirements: 1.1, 1.2_

  - [x] 1.2 Create `enrollments` migration
    - Columns: `id`, `student_id` (FK→users), `academic_year_id` (FK→academic_years), `class_id` (FK→my_classes), `section_id` (FK→sections), `roll_no` (VARCHAR 20 nullable), `enrollment_status` ENUM('active','superseded','finalized') default 'active', timestamps
    - UNIQUE KEY on `(student_id, academic_year_id)`
    - Indexes on `enrollment_status`, `(class_id, section_id, academic_year_id)`
    - _Requirements: 2.1, 2.3, 2.5, 2.6_

  - [x] 1.3 Create `promotion_batches` migration
    - Columns: `id`, `from_academic_year_id`, `to_academic_year_id`, `from_class_id`, `to_class_id`, `redistribution_mode` ENUM('keep_same','random','balanced','manual') default 'random', `status` ENUM('draft','finalized','rolled_back') default 'draft', `student_count` INT default 0, `created_by` (FK→users), `finalized_at` TIMESTAMP nullable, timestamps
    - Indexes on `status`, `(from_academic_year_id, from_class_id)`
    - _Requirements: 4.1, 4.2_

  - [x] 1.4 Create `promotion_drafts` migration
    - Columns: `id`, `promotion_batch_id` (FK→promotion_batches CASCADE), `student_id` (FK→users), `current_section_id` (FK→sections SET NULL), `proposed_section_id` (FK→sections SET NULL), `is_locked` TINYINT default 0, `redistribution_group` VARCHAR 20 nullable, `eligibility_status` ENUM('passed','held','conditional') default 'passed', `yearly_average` DECIMAL(6,2) nullable, `remarks` TEXT nullable, timestamps
    - UNIQUE KEY on `(promotion_batch_id, student_id)`
    - Index on `(promotion_batch_id, proposed_section_id)`
    - _Requirements: 5.1, 5.6, 9.1_

  - [x] 1.5 Create `promotion_history` migration
    - Columns: `id`, `promotion_batch_id` (FK→promotion_batches), `student_id` (FK→users), `old_enrollment_id` INT nullable, `new_enrollment_id` INT nullable, `old_class_id` INT nullable, `old_section_id` INT nullable, `old_session` VARCHAR 20 nullable, `action_type` ENUM('promoted','rolled_back'), `action_date` TIMESTAMP default CURRENT_TIMESTAMP, `performed_by` (FK→users)
    - No `updated_at` — append-only table
    - Indexes on `promotion_batch_id`, `student_id`
    - _Requirements: 16.1, 16.2, 12.2_

  - [ ] 1.6 Run migrations and verify all 5 tables exist with correct columns
    - `php artisan migrate`
    - Confirm foreign keys and indexes are created

- [-] 2. Eloquent Models

  - [ ] 2.1 Create `App\Models\AcademicYear`
    - Fillable: `year_name`, `is_active`
    - `scopeActive()` query scope
    - `activate()` method: sets all others to `is_active=0`, then sets self to `is_active=1`
    - `hasMany(Enrollment)`
    - _Requirements: 1.1, 1.2, 1.3_

  - [ ] 2.2 Create `App\Models\Enrollment`
    - Fillable: `student_id`, `academic_year_id`, `class_id`, `section_id`, `roll_no`, `enrollment_status`
    - Relations: `student()→User`, `academicYear()→AcademicYear`, `myClass()→MyClass`, `section()→Section`
    - Scopes: `scopeActive()`, `scopeForYear(int $yearId)`
    - Override `save()`: throw `\RuntimeException` if model already exists and `enrollment_status` is `superseded` or `finalized` (immutability guard)
    - _Requirements: 2.3, 12.1_

  - [ ] 2.3 Create `App\Models\PromotionBatch`
    - Fillable: all except `id`, timestamps
    - Relations: `fromYear()`, `toYear()`, `fromClass()`, `toClass()`, `createdBy()`, `drafts()→hasMany(PromotionDraft)`, `history()→hasMany(PromotionHistory)`
    - Scopes: `scopeDraft()`, `scopeFinalized()`
    - _Requirements: 4.1, 4.2_

  - [ ] 2.4 Create `App\Models\PromotionDraft`
    - Fillable: all except `id`, timestamps
    - Casts: `is_locked→boolean`, `yearly_average→float`
    - Relations: `batch()→PromotionBatch`, `student()→User`, `currentSection()→Section`, `proposedSection()→Section`
    - _Requirements: 5.1, 9.1_

  - [ ] 2.5 Create `App\Models\PromotionHistory`
    - Fillable: all except `id`
    - `public $timestamps = false`
    - Override `save()`: throw `\RuntimeException` if model already exists (append-only guard)
    - Relations: `batch()`, `student()`, `performedBy()`
    - _Requirements: 16.1, 12.2_

- [ ] 3. Service Layer — EnrollmentService

  - [ ] 3.1 Create `App\Services\EnrollmentService`
    - `createForAdmission(int $studentId, int $classId, int $sectionId, int $yearId): Enrollment`
      - Generates `roll_no` as next sequential number for the section/year
      - Creates `enrollments` row with `enrollment_status = active`
    - `currentEnrollment(int $studentId): ?Enrollment`
      - Returns latest `active` enrollment for the student
    - `nextRollNo(int $yearId, int $classId, int $sectionId): string`
      - Queries max roll_no for the combination, increments by 1
    - `supersede(Enrollment $enrollment): void`
      - Uses `DB::table()` to set `enrollment_status = superseded` (bypasses model guard)
    - _Requirements: 2.1, 2.2, 2.4, 2.5_

- [ ] 4. Service Layer — RedistributionService

  - [ ] 4.1 Create `App\Services\RedistributionService`
    - `distribute(Collection $drafts, Collection $targetSections, string $mode): Collection`
      - Dispatches to private method based on mode
    - `keepSame(Collection $drafts, Collection $targetSections): Collection`
      - Matches section name in target class; falls back to first section
    - `random(Collection $drafts, Collection $targetSections): Collection`
      - Shuffles pool, round-robin assigns, max variance of 1 between sections
    - `balanced(Collection $drafts, Collection $targetSections): Collection`
      - Priority: capacity → gender balance (±1) → sibling pairs → locked students
    - `manual(Collection $drafts): Collection`
      - Sets all `proposed_section_id = null`
    - `rebalanceUnlocked(Collection $drafts, Collection $targetSections): Collection`
      - Runs balanced on unlocked only, preserves locked assignments
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 8.3, 8.4_

- [ ] 5. Service Layer — PromotionBatchService

  - [ ] 5.1 Create `App\Services\PromotionBatchService`
    - `buildPool(int $fromClassId, int $fromYearId): Collection`
      - Loads all active enrollments for the class/year across ALL sections
      - Returns merged collection of student records
    - `evaluateEligibility(Collection $students, int $classId, string $session): Collection`
      - Calls existing `ExamRepo::getSessionAverage()` per student
      - Evaluates against active `PromotionRule` records
      - Tags each student as `passed`, `held`, or `conditional`
    - `initBatch(array $params, string $mode): PromotionBatch`
      - Validates no duplicate draft/finalized batch exists
      - Creates `promotion_batches` row
      - Calls `buildPool()` + `evaluateEligibility()`
      - Calls `RedistributionService::distribute()` to generate drafts
      - Creates `promotion_drafts` rows in bulk insert
    - `finalize(PromotionBatch $batch, int $adminId): void`
      - Wraps entire sequence in `DB::transaction()`
      - Creates new `enrollments`, supersedes old, writes `promotion_history`, updates `student_records`, inserts into legacy `promotions` table
      - Sets batch `status = finalized`
    - `rollback(PromotionBatch $batch, int $adminId): void`
      - Wraps in `DB::transaction()`
      - Deletes new enrollments, restores superseded, restores `student_records`, writes rollback history
      - Sets batch `status = rolled_back`
    - `regenerateDrafts(PromotionBatch $batch): void`
      - Deletes existing drafts for batch
      - Re-runs `distribute()` with original mode
    - _Requirements: 4.3, 4.4, 4.5, 5.1, 10.1–10.8, 11.1–11.8, 14.4_

- [ ] 6. Controllers

  - [ ] 6.1 Create `App\Http\Controllers\SupportTeam\PromotionBatchController`
    - `index()` — list all batches with status badges, paginated
    - `create()` — form: source year/class, target year/class, redistribution mode
    - `store(Request $req)` — validate, call `PromotionBatchService::initBatch()`, redirect to workspace
    - `workspace(PromotionBatch $batch)` — load batch + drafts + sections, pass to view
    - `shuffle(PromotionBatch $batch)` — call `regenerateDrafts()`, return JSON
    - `finalize(PromotionBatch $batch)` — call `PromotionBatchService::finalize()`, redirect to summary
    - `rollback(PromotionBatch $batch)` — call `PromotionBatchService::rollback()`, redirect to batch list
    - `destroy(PromotionBatch $batch)` — only if status=draft, delete batch + drafts
    - `summary(PromotionBatch $batch)` — show finalization summary
    - _Requirements: 4.1, 5.7, 10.8, 11.8_

  - [ ] 6.2 Create `App\Http\Controllers\SupportTeam\PromotionWorkspaceController`
    - `updateDraft(Request $req, PromotionDraft $draft)` — PATCH, update `proposed_section_id`, return JSON
    - `toggleLock(Request $req, PromotionDraft $draft)` — PATCH, toggle `is_locked`, return JSON
    - `addSection(Request $req)` — POST, create new Section for target class, return JSON with new section data
    - `removeSection(Section $section)` — DELETE, verify empty, delete section, return JSON
    - _Requirements: 7.3, 8.1, 8.2, 9.1, 9.2, 14.1_

  - [ ] 6.3 Create `App\Http\Controllers\SuperAdmin\AcademicYearController`
    - `index()` — list all academic years
    - `store(Request $req)` — validate unique year_name, create
    - `activate(AcademicYear $year)` — call `year->activate()`
    - `destroy(AcademicYear $year)` — only if no enrollments reference it
    - _Requirements: 1.1–1.5_

- [ ] 7. Routes

  - [ ] 7.1 Add promotion batch routes to `routes/web.php` under `SupportTeam` namespace + `teamSA` middleware
    ```php
    Route::prefix('promotion')->group(function () {
        Route::get('/batches',                          'PromotionBatchController@index')->name('promotion.batches.index');
        Route::get('/batches/create',                   'PromotionBatchController@create')->name('promotion.batches.create');
        Route::post('/batches',                         'PromotionBatchController@store')->name('promotion.batches.store');
        Route::get('/batches/{batch}',                  'PromotionBatchController@workspace')->name('promotion.batches.workspace');
        Route::post('/batches/{batch}/shuffle',         'PromotionBatchController@shuffle')->name('promotion.batches.shuffle');
        Route::post('/batches/{batch}/finalize',        'PromotionBatchController@finalize')->name('promotion.batches.finalize');
        Route::post('/batches/{batch}/rollback',        'PromotionBatchController@rollback')->name('promotion.batches.rollback');
        Route::delete('/batches/{batch}',               'PromotionBatchController@destroy')->name('promotion.batches.destroy');
        Route::get('/batches/{batch}/summary',          'PromotionBatchController@summary')->name('promotion.batches.summary');
        Route::patch('/drafts/{draft}',                 'PromotionWorkspaceController@updateDraft')->name('promotion.drafts.update');
        Route::patch('/drafts/{draft}/lock',            'PromotionWorkspaceController@toggleLock')->name('promotion.drafts.lock');
        Route::post('/sections',                        'PromotionWorkspaceController@addSection')->name('promotion.sections.add');
        Route::delete('/sections/{section}',            'PromotionWorkspaceController@removeSection')->name('promotion.sections.remove');
    });
    ```
    - _Requirements: 4.1, 8.1, 8.2, 9.1_

  - [ ] 7.2 Add academic year routes under `SuperAdmin` namespace + `super_admin` middleware
    ```php
    Route::get('/academic-years',                       'AcademicYearController@index')->name('academic_years.index');
    Route::post('/academic-years',                      'AcademicYearController@store')->name('academic_years.store');
    Route::patch('/academic-years/{year}/activate',     'AcademicYearController@activate')->name('academic_years.activate');
    Route::delete('/academic-years/{year}',             'AcademicYearController@destroy')->name('academic_years.destroy');
    ```
    - _Requirements: 1.1–1.5_

  - [ ] 7.3 Add "Promotion Batches" to the Promotion submenu in `menu.blade.php`
    - Add under the existing Promotion submenu (after Promotion Rules)
    - Active detection: `Route::is('promotion.batches.*')`
    - _Requirements: 4.1_

- [ ] 8. Views — Batch List and Create Form

  - [ ] 8.1 Create `pages/support_team/promotion/batches/index.blade.php`
    - Table of all batches: from class/year → to class/year, mode, status badge, student count, created by, actions
    - Status badges: draft (blue), finalized (green), rolled_back (red)
    - Actions: Open Workspace (draft), View Summary (finalized), Rollback (finalized), Delete (draft)
    - "New Promotion Batch" button
    - _Requirements: 4.1, 11.8_

  - [ ] 8.2 Create `pages/support_team/promotion/batches/create.blade.php`
    - Step 1: Select source academic year + source class
    - Step 2: Select target academic year + target class
    - Step 3: Choose redistribution mode (radio buttons with descriptions)
    - Preview: shows eligible student count before submitting
    - _Requirements: 4.1, 5.1–5.5_

- [ ] 9. Views — Interactive Workspace

  - [ ] 9.1 Create `pages/support_team/promotion/batches/workspace.blade.php`
    - Three-panel layout (CSS grid: 25% / 50% / 25%)
    - Include SortableJS from CDN
    - Include Alpine.js (already in project or add CDN)
    - Pass batch data, drafts, sections, students as JSON to Alpine component
    - _Requirements: 6.1, 7.1, 8.1_

  - [ ] 9.2 Implement Left Panel in workspace view
    - Student list with search input (debounced 300ms filter)
    - Gender filter buttons (All / Male / Female)
    - Previous section filter dropdown
    - Each student row: name, gender icon (♂/♀), score badge, prev section tag, lock icon if locked, dimmed if assigned
    - _Requirements: 6.1–6.5_

  - [ ] 9.3 Implement Center Panel — Section Distribution Board
    - One card per target section
    - Card header: section name, capacity bar (green/yellow/red), student count / capacity
    - Card stats: boys count, girls count, average score
    - Student chips inside each card (SortableJS group)
    - Each chip: student name, gender icon, lock icon if locked
    - Locked chips have CSS `cursor: not-allowed` and are excluded from SortableJS
    - Overflow confirmation dialog when dropping onto full section
    - _Requirements: 7.1–7.8_

  - [ ] 9.4 Implement Right Panel — Controls
    - Shuffle Again button → POST to `promotion.batches.shuffle`
    - Auto Balance button → calls `rebalanceUnlocked()` via AJAX
    - Undo button → calls `workspace.undo()` in Alpine
    - Reset button → confirms then calls `regenerateDrafts()` via AJAX
    - Add Section button → opens modal, POST to `promotion.sections.add`
    - Remove Section button → activates remove mode on section cards
    - Finalize button (disabled until all assigned) → confirmation dialog → POST to `promotion.batches.finalize`
    - Live stats: Total students, Assigned, Unassigned, Held back
    - _Requirements: 8.1–8.8_

  - [ ] 9.5 Implement Alpine.js workspace state management
    - `drafts`, `sections`, `students` objects initialized from PHP-passed JSON
    - `moveStudent(studentId, fromSectionId, toSectionId)` — updates state + schedules save
    - `scheduleSave(draftId, sectionId)` — 500ms debounce
    - `saveDraft(draftId, sectionId)` — PATCH to `promotion.drafts.update`
    - `toggleLock(draftId)` — PATCH to `promotion.drafts.lock`
    - `undo()` — pops undoStack, reverts state, saves
    - `undoStack` array capped at 20 items
    - `capacityColor(sectionId)` — returns 'green'/'yellow'/'red'
    - `filteredStudents` computed getter with search + gender + section filters
    - `canFinalize` computed getter: all drafts have non-null `proposed_section_id`
    - _Requirements: 7.3, 8.5, 8.6, 9.1–9.4, 14.1, 14.2_

  - [ ] 9.6 Implement SortableJS drag-and-drop
    - Initialize one Sortable instance per section card
    - `group: 'students'` to allow cross-section dragging
    - `filter: '.locked'` to prevent locked chip dragging
    - `onEnd` handler calls `workspace.moveStudent()`
    - _Requirements: 7.2, 7.3, 7.8_

- [ ] 10. Views — Summary and Academic Years

  - [ ] 10.1 Create `pages/support_team/promotion/batches/summary.blade.php`
    - Finalization summary: batch details, counts (promoted/held/conditional)
    - Per-section breakdown table: section name, student count, boys, girls, avg score
    - Rollback button (if within allowed window)
    - Download CSV button (export promotion list)
    - _Requirements: 10.8, 11.1_

  - [ ] 10.2 Create `pages/super_admin/academic_years/index.blade.php`
    - List of all academic years with active badge
    - Create form (year_name input)
    - Activate button per row
    - Delete button (disabled if enrollments exist)
    - _Requirements: 1.1–1.5_

- [ ] 11. Admission Integration

  - [ ] 11.1 Update `StudentRecordController@store` to also create an `enrollments` row
    - After creating the `student_records` row, call `EnrollmentService::createForAdmission()`
    - Use the active `AcademicYear` (or fall back to `settings.current_session` if no active year)
    - _Requirements: 2.1, 13.1_

  - [ ] 11.2 Update `StudentRecordController@bulkImport` to also create `enrollments` rows
    - Same pattern as 11.1 for each imported student
    - _Requirements: 2.1, 13.1_

- [ ] 12. Checkpoint — Smoke Test Core Flow
  - Run `php artisan migrate` — confirm all 5 tables exist
  - Admit one test student — confirm `enrollments` row created
  - Create a promotion batch — confirm `promotion_batches` + `promotion_drafts` rows created
  - Finalize — confirm new `enrollments` rows, `student_records` updated, `promotion_history` written
  - Rollback — confirm new enrollments deleted, old enrollments restored

- [ ] 13. Unit Tests

  - [ ]* 13.1 `RedistributionServiceTest` — all 4 modes, edge cases (empty pool, single section, capacity overflow, locked students)
  - [ ]* 13.2 `EnrollmentServiceTest` — roll_no uniqueness, immutability guard throws on update
  - [ ]* 13.3 `PromotionBatchServiceTest` — pool building, eligibility evaluation, finalize/rollback atomicity
  - [ ]* 13.4 `AcademicYearTest` — `activate()` deactivates all others, unique year_name validation

- [ ] 14. Feature (Integration) Tests

  - [ ]* 14.1 `POST /promotion/batches` — creates batch + drafts, redirects to workspace
  - [ ]* 14.2 `PATCH /promotion/drafts/{id}` — persists section assignment, returns JSON
  - [ ]* 14.3 `POST /promotion/batches/{id}/finalize` — creates enrollments, updates student_records, writes history
  - [ ]* 14.4 `POST /promotion/batches/{id}/rollback` — restores previous state, preserves history rows
  - [ ]* 14.5 Workspace view renders with correct student chips and section cards

- [ ] 15. Commit and Push
  - Stage all new files
  - Commit with message: `feat: promotion enrollment redistribution module`
  - Push to remote main

---

## Notes

- Tasks marked `*` are optional for MVP but recommended for production safety
- SortableJS can be loaded from CDN: `https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js`
- Alpine.js can be loaded from CDN: `https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js`
- The existing `PromotionController` and `TermSetupController` remain untouched throughout
- All finalization and rollback operations must be wrapped in `DB::transaction()` — no partial commits
- The `promotion_history` table is append-only — the model's `save()` override enforces this at the application layer
- Auto-save uses a 500ms debounce to avoid flooding the server during rapid drag-and-drop
