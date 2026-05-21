# Design Document: Student Promotion Engine
## Overview

The Student Promotion Engine replaces the existing hardcoded average-based auto-promotion (`TermSetupController@autoPromote`) and the manual per-class promotion (`PromotionController`) with a configurable, rule-driven system. The engine evaluates multiple promotion rules per student, stores a preview for admin review, supports manual overrides and bulk actions, and writes an immutable audit log for every finalized decision.

The engine is implemented as a Laravel 8 service class (`PromotionEngineService`) backed by six new database tables, a dedicated controller (`PromotionEngineController`), and a set of Blade views. It integrates with the existing `RulesEngine` service, `StudentRepo`, `ExamRepo`, and the `settings` table.

### Key Design Principles

1. **Non-destructive**  existing `student_records`, `marks`, `exam_records`, and `promotions` rows are never modified or deleted.
2. **Preview-before-commit**  every run produces a stored preview that the admin reviews and can override before finalizing.
3. **Append-only audit trail**  `promotion_logs` rows are never updated or deleted.
4. **Scope-aware rule evaluation**  rules can be scoped to the whole school, a class, a department, or an academic year; more specific scopes override broader ones.
5. **Graceful degradation**  students with missing data receive `Pending Review` rather than an incorrect automatic decision.

---

## Architecture

```

                     HTTP Layer                                  
  PromotionEngineController  (SuperAdmin middleware)             
  Routes: /promotion-engine/*                                    

                         

                  PromotionEngineService                         
  runPromotion()   evaluateStudent()   finalizeRun()             
  resolveNextClass()  computeYearlyAverage()                     
  applyOverride()  bulkOverride()  exportRun()                   

                                           
    
 Promotion       Existing         Existing        
 Rule Models     RulesEngine      Repos           
 (new tables)    (fallback)       StudentRepo     
                                  ExamRepo        
    
```

The controller handles HTTP concerns only. All business logic lives in `PromotionEngineService`. The service reads from the new tables and existing tables but writes only to the new tables (plus creating new `student_records` rows on finalization).

---

## Components and Interfaces

### PromotionEngineController

**Namespace:** `App\Http\Controllers\SuperAdmin`  
**Middleware:** `super_admin`  
**Route prefix:** `/promotion-engine`

| Method | Route | Action | View |
|--------|-------|--------|------|
| GET | `/promotion-engine` | `dashboard()` | `promotion-engine.dashboard` |
| GET | `/promotion-engine/rules` | `rules()` | `promotion-engine.rules.index` |
| POST | `/promotion-engine/rules` | `storeRule()` | redirect |
| PUT | `/promotion-engine/rules/{id}` | `updateRule()` | redirect |
| DELETE | `/promotion-engine/rules/{id}` | `destroyRule()` | redirect |
| GET | `/promotion-engine/progressions` | `progressions()` | `promotion-engine.progressions.index` |
| POST | `/promotion-engine/progressions` | `storeProgression()` | redirect |
| PUT | `/promotion-engine/progressions/{id}` | `updateProgression()` | redirect |
| DELETE | `/promotion-engine/progressions/{id}` | `destroyProgression()` | redirect |
| POST | `/promotion-engine/run` | `initiateRun()` | redirect to preview |
| GET | `/promotion-engine/preview/{run_id}` | `preview()` | `promotion-engine.preview` |
| POST | `/promotion-engine/preview/{run_id}/override` | `applyOverride()` | JSON |
| POST | `/promotion-engine/preview/{run_id}/bulk-override` | `bulkOverride()` | JSON |
| POST | `/promotion-engine/preview/{run_id}/finalize` | `finalizeRun()` | redirect to history |
| GET | `/promotion-engine/history` | `history()` | `promotion-engine.history` |
| GET | `/promotion-engine/export/{run_id}` | `export()` | CSV/PDF download |

### PromotionEngineService

**Namespace:** `App\Services`

```php
class PromotionEngineService
{
    // Initiate a new promotion run for a given academic year and optional scope
    public function runPromotion(string $academicYear, ?int $classId = null): PromotionRun;

    // Evaluate all active rules for a single student record
    public function evaluateStudent(StudentRecord $sr, Collection $rules, string $academicYear): array;
    // Returns: ['status' => string, 'failed_rules' => array, 'yearly_avg' => float, ...]

    // Resolve the next class for a student (DB mapping first, RulesEngine fallback)
    public function resolveNextClass(int $fromClassId): ?MyClass;

    // Compute yearly average from exam_records
    public function computeYearlyAverage(int $studentId, string $academicYear): ?float;

    // Compute attendance rate from attendance_records
    public function computeAttendanceRate(int $studentId, string $academicYear): float;

    // Check fee clearance
    public function hasClearedFees(int $studentId): bool;

    // Count failed subjects (cum_ave below pass mark)
    public function countFailedSubjects(int $studentId, string $academicYear, int $passMark): int;

    // Check core subject scores
    public function checkCoreSubjects(int $studentId, string $academicYear, float $threshold): array;
    // Returns: ['passed' => bool, 'failures' => [['subject' => ..., 'score' => ..., 'threshold' => ...]]]

    // Determine promotion status from rule evaluation results
    public function determineStatus(array $ruleResults): string;

    // Apply a manual override to a preview record
    public function applyOverride(int $previewId, string $status, string $notes, int $adminId): PromotionPreview;

    // Apply bulk override to multiple preview records
    public function bulkOverride(array $previewIds, string $status, string $notes, int $adminId): int;

    // Finalize a run: write student_records + promotion_logs
    public function finalizeRun(int $runId, int $adminId): PromotionRun;

    // Get active rules for a student, respecting scope priority
    public function getApplicableRules(int $classId, ?int $departmentId, string $academicYear): Collection;

    // Export a run as CSV or PDF
    public function exportRun(int $runId, string $format): string; // returns file path
}
```

### Rule Evaluator (internal to PromotionEngineService)

The service uses a private `evaluateRule(PromotionRule $rule, array $studentData): array` method that dispatches to a dedicated handler per rule type:

| Rule Type | Handler Logic |
|-----------|--------------|
| `min_overall_average` | Compare `yearly_avg` against threshold using `condition_operator` |
| `core_subject_min_score` | Check each core subject's `cum_ave` in `marks` |
| `max_failed_subjects` | Count subjects where `cum_ave < custom_pass_mark` |
| `min_attendance_rate` | Compare attendance rate against threshold |
| `fee_clearance_required` | Check `payment_records` for unpaid rows |
| `discipline_restriction` | Reserved for future integration; currently returns pass |
| `conditional_promotion` | Evaluated same as other rules but flagged as conditional |

---

## Data Models

### New Tables

#### `class_progressions`

Stores admin-defined class-to-class progression mappings.

```sql
CREATE TABLE class_progressions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_class_id   INT UNSIGNED NOT NULL,
    to_class_id     INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    UNIQUE KEY uq_from_class (from_class_id),
    FOREIGN KEY (from_class_id) REFERENCES my_classes(id) ON DELETE CASCADE,
    FOREIGN KEY (to_class_id)   REFERENCES my_classes(id) ON DELETE CASCADE
);
```

**Eloquent Model:** `App\Models\ClassProgression`  
**Fillable:** `from_class_id`, `to_class_id`  
**Relations:** `fromClass()`  `MyClass`, `toClass()`  `MyClass`

---

#### `promotion_rules`

Stores configurable promotion rules.

```sql
CREATE TABLE promotion_rules (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(191) NOT NULL,
    rule_type           ENUM('min_overall_average','core_subject_min_score',
                             'max_failed_subjects','min_attendance_rate',
                             'fee_clearance_required','discipline_restriction',
                             'conditional_promotion') NOT NULL,
    condition_operator  ENUM('gte','lte','gt','lt','eq') NULL,
    threshold_value     DECIMAL(8,2) NULL,
    scope_type          ENUM('school','class','department','year') NOT NULL DEFAULT 'school',
    scope_class_id      INT UNSIGNED NULL,
    scope_department_id INT UNSIGNED NULL,
    scope_year          VARCHAR(20) NULL,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    description         TEXT NULL,
    created_by          INT UNSIGNED NOT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    INDEX idx_active_scope (is_active, scope_type),
    FOREIGN KEY (scope_class_id)      REFERENCES my_classes(id) ON DELETE SET NULL,
    FOREIGN KEY (scope_department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)          REFERENCES users(id) ON DELETE CASCADE
);
```

**Eloquent Model:** `App\Models\PromotionRule`  
**Fillable:** `name`, `rule_type`, `condition_operator`, `threshold_value`, `scope_type`, `scope_class_id`, `scope_department_id`, `scope_year`, `is_active`, `description`, `created_by`  
**Scopes:** `scopeActive()`, `scopeForClass(int $classId)`, `scopeForDepartment(int $deptId)`

---

#### `promotion_runs`

Tracks each execution of the promotion engine.

```sql
CREATE TABLE promotion_runs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year   VARCHAR(20) NOT NULL,
    scope_class_id  INT UNSIGNED NULL,
    status          ENUM('pending','previewing','finalized','cancelled') NOT NULL DEFAULT 'pending',
    initiated_by    INT UNSIGNED NOT NULL,
    finalized_by    INT UNSIGNED NULL,
    initiated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalized_at    TIMESTAMP NULL,
    student_count   INT UNSIGNED NOT NULL DEFAULT 0,
    notes           TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX idx_year_status (academic_year, status),
    FOREIGN KEY (scope_class_id) REFERENCES my_classes(id) ON DELETE SET NULL,
    FOREIGN KEY (initiated_by)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (finalized_by)   REFERENCES users(id) ON DELETE SET NULL
);
```

**Eloquent Model:** `App\Models\PromotionRun`  
**Fillable:** `academic_year`, `scope_class_id`, `status`, `initiated_by`, `finalized_by`, `initiated_at`, `finalized_at`, `student_count`, `notes`  
**Relations:** `previews()`  `hasMany(PromotionPreview)`, `logs()`  `hasMany(PromotionLog)`, `initiatedBy()`  `User`, `scopeClass()`  `MyClass`

---

#### `promotion_previews`

Stores per-student results of a promotion run before finalization.

```sql
CREATE TABLE promotion_previews (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_id              INT UNSIGNED NOT NULL,
    student_id          INT UNSIGNED NOT NULL,
    current_class_id    INT UNSIGNED NOT NULL,
    current_section_id  INT UNSIGNED NOT NULL,
    target_class_id     INT UNSIGNED NULL,
    target_section_id   INT UNSIGNED NULL,
    yearly_average      DECIMAL(6,2) NULL,
    failed_subjects     INT UNSIGNED NOT NULL DEFAULT 0,
    attendance_rate     DECIMAL(5,2) NULL,
    fee_cleared         TINYINT(1) NOT NULL DEFAULT 1,
    computed_status     ENUM('Promoted','Conditionally Promoted','Repeated','Pending Review') NOT NULL,
    override_status     ENUM('Promoted','Conditionally Promoted','Repeated','Pending Review') NULL,
    override_by         INT UNSIGNED NULL,
    override_reason     TEXT NULL,
    overridden_at       TIMESTAMP NULL,
    failed_rules        JSON NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    INDEX idx_run_status (run_id, computed_status),
    INDEX idx_run_student (run_id, student_id),
    FOREIGN KEY (run_id)             REFERENCES promotion_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)         REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_class_id)   REFERENCES my_classes(id) ON DELETE CASCADE,
    FOREIGN KEY (target_class_id)    REFERENCES my_classes(id) ON DELETE SET NULL,
    FOREIGN KEY (override_by)        REFERENCES users(id) ON DELETE SET NULL
);
```

The `failed_rules` JSON column stores an array of objects:
```json
[
  {
    "rule_id": 3,
    "rule_name": "Minimum Overall Average",
    "rule_type": "min_overall_average",
    "threshold": 50,
    "actual": 43.5,
    "is_conditional": false
  }
]
```

**Eloquent Model:** `App\Models\PromotionPreview`  
**Fillable:** all columns except `id`, `created_at`, `updated_at`  
**Casts:** `failed_rules`  `array`  
**Relations:** `run()`  `PromotionRun`, `student()`  `User`, `currentClass()`  `MyClass`, `targetClass()`  `MyClass`, `overrideBy()`  `User`  
**Computed accessor:** `effectiveStatus()`  returns `override_status ?? computed_status`

---

#### `promotion_logs`

Immutable audit trail. Rows are never updated or deleted.

```sql
CREATE TABLE promotion_logs (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_id              INT UNSIGNED NOT NULL,
    student_id          INT UNSIGNED NOT NULL,
    previous_class_id   INT UNSIGNED NOT NULL,
    new_class_id        INT UNSIGNED NOT NULL,
    previous_section_id INT UNSIGNED NOT NULL,
    new_section_id      INT UNSIGNED NOT NULL,
    academic_year       VARCHAR(20) NOT NULL,
    promotion_status    ENUM('Promoted','Conditionally Promoted','Repeated','Pending Review') NOT NULL,
    promoted_by         INT UNSIGNED NOT NULL,
    promotion_date      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_override         TINYINT(1) NOT NULL DEFAULT 0,
    override_reason     TEXT NULL,
    notes               TEXT NULL,
    created_at          TIMESTAMP NULL,

    INDEX idx_student_year (student_id, academic_year),
    INDEX idx_run (run_id),
    INDEX idx_status (promotion_status),
    FOREIGN KEY (run_id)              REFERENCES promotion_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)          REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (previous_class_id)   REFERENCES my_classes(id) ON DELETE CASCADE,
    FOREIGN KEY (new_class_id)        REFERENCES my_classes(id) ON DELETE CASCADE,
    FOREIGN KEY (promoted_by)         REFERENCES users(id) ON DELETE CASCADE
);
```

No `updated_at` column  this table is append-only by design. The Eloquent model sets `public $timestamps = false` and overrides `save()` to throw if an existing record is being updated.

**Eloquent Model:** `App\Models\PromotionLog`  
**Fillable:** all columns except `id`  
**Relations:** `run()`  `PromotionRun`, `student()`  `User`, `previousClass()`  `MyClass`, `newClass()`  `MyClass`, `promotedBy()`  `User`

---

### Modified Tables

#### `subjects`  add `subject_category` column

```sql
ALTER TABLE subjects
    ADD COLUMN subject_category ENUM('core','elective','optional') NOT NULL DEFAULT 'elective'
    AFTER master_subject_id;
```

The `Subject` model gains:
- `subject_category` added to `$fillable`
- `isCoreSubject(): bool` method returning `$this->subject_category === 'core'`
- `scopeCore(Builder $q)` query scope

---

### Existing Tables Used (read-only during evaluation)

| Table | Fields read |
|-------|-------------|
| `student_records` | `user_id`, `my_class_id`, `section_id`, `session`, `grad` |
| `exam_records` | `student_id`, `exam_id`, `year`, `ave` |
| `marks` | `student_id`, `subject_id`, `year`, `cum_ave` |
| `attendance_records` | `student_id`, `session_id`, `status` |
| `attendance_sessions` | `id`, `year` |
| `payment_records` | `student_id`, `paid` |
| `exams` | `id`, `year`, `status` |
| `my_classes` | `id`, `name` |
| `sections` | `id`, `my_class_id` |
| `settings` | `type`, `description` |

---

## Rule Evaluation Algorithm

### Step 1  Pre-flight checks

Before evaluating any student, `runPromotion()` performs:

1. Verify the academic year exists in `exams` and all exams for that year have `status = 'finalized'`. If not, abort with an error.
2. Load all active `PromotionRule` records.
3. Load all `ClassProgression` mappings into a keyed collection for O(1) lookup.
4. Load the `custom_pass_mark` setting (default 50).
5. Create a `PromotionRun` record with `status = 'pending'`.

### Step 2  Student iteration

For each active `StudentRecord` in the target scope (non-graduated, matching session):

```
foreach student_record as sr:
    studentData = gatherStudentData(sr, academicYear)
    rules       = getApplicableRules(sr.my_class_id, sr.department_id, academicYear)
    ruleResults = []

    foreach rules as rule:
        result = evaluateRule(rule, studentData)
        ruleResults[] = result

    status = determineStatus(ruleResults)
    nextClass, nextSection = resolveNextClass(sr.my_class_id, status)

    create PromotionPreview(run_id, sr, status, nextClass, nextSection, ruleResults, studentData)
```

### Step 3  Scope priority resolution

`getApplicableRules()` loads rules in this priority order (most specific wins per rule type):

```
Priority 1 (highest): scope_type = 'class'      AND scope_class_id = student's class
Priority 2:           scope_type = 'department'  AND scope_department_id = student's dept
Priority 3:           scope_type = 'year'        AND scope_year = academicYear
Priority 4 (lowest):  scope_type = 'school'
```

For each `rule_type`, only the highest-priority matching rule is applied. This is implemented by grouping rules by `rule_type` and selecting the minimum-priority-number entry.

### Step 4  Status determination

```
determineStatus(ruleResults):
    failed = ruleResults.filter(r => r.passed == false)

    if failed.isEmpty():
        return 'Promoted'

    if failed.all(r => r.is_conditional == true):
        return 'Conditionally Promoted'

    if studentData.exam_count < expected_exam_count:
        return 'Pending Review'

    return 'Repeated'
```

`Pending Review` also applies when:
- No class progression exists and no RulesEngine fallback is found
- Target class does not exist in `my_classes`

### Step 5  Next class resolution

```
resolveNextClass(fromClassId, status):
    if status == 'Repeated':
        return (fromClass, fromSection)  // stay in same class

    progression = ClassProgression.where('from_class_id', fromClassId).first()
    if progression:
        nextClass = progression.toClass
    else:
        nextClassName = RulesEngine::getNextClassInOrder(fromClass.name)
        nextClass = MyClass.where('name', nextClassName).first()

    if nextClass == null:
        return null  // triggers Pending Review

    nextSection = nextClass.sections.first()  // first active section
    return (nextClass, nextSection)
```

### Step 6  Finalization

When the admin clicks "Finalize":

```
foreach preview in run.previews:
    effectiveStatus = preview.override_status ?? preview.computed_status

    if effectiveStatus in ['Promoted', 'Conditionally Promoted']:
        newClassId   = preview.target_class_id
        newSectionId = preview.target_section_id
    else:  // Repeated or Pending Review
        newClassId   = preview.current_class_id
        newSectionId = preview.current_section_id

    // Create new student_record for new session (never update old one)
    StudentRecord.create({
        user_id:      preview.student_id,
        my_class_id:  newClassId,
        section_id:   newSectionId,
        session:      newAcademicYear,
        // copy other fields from current record
    })

    // Write immutable log
    PromotionLog.create({
        run_id:             run.id,
        student_id:         preview.student_id,
        previous_class_id:  preview.current_class_id,
        new_class_id:       newClassId,
        academic_year:      run.academic_year,
        promotion_status:   effectiveStatus,
        promoted_by:        adminId,
        is_override:        preview.override_status != null ? 1 : 0,
        override_reason:    preview.override_reason,
    })

run.update(['status' => 'finalized', 'finalized_by' => adminId, 'finalized_at' => now()])
```

---

## View Structure

All views live under `resources/views/pages/super_admin/promotion_engine/`.

| File | Purpose |
|------|---------|
| `dashboard.blade.php` | Stats overview, run initiation form, year selector |
| `rules/index.blade.php` | List all promotion rules, create/edit/delete modals |
| `progressions/index.blade.php` | List class progressions, create/edit/delete modals |
| `preview.blade.php` | Per-student preview table with filters, override controls, bulk actions, finalize button |
| `history.blade.php` | Promotion log table with filters by student, class, year, status |
| `partials/rule_form.blade.php` | Shared rule create/edit form partial |
| `partials/student_row.blade.php` | Single student row in preview table |

### Dashboard layout

```

  Promotion Engine Dashboard          [Year selector ]  

 Total     Promoted  Cond.     Repeated  Pending     
 320       280 (87%) 15 (5%)   18 (6%)   7 (2%)      

  Per-class breakdown table                              

  [Initiate New Promotion Run]  [View History]           

```

### Preview page layout

```

  Preview: Run #12  2024-2025                           
  Filters: [Class ] [Status ] [Search name...]        

  Bulk: [ Select All] [Approve] [Reject] [Repeat]      

      Student   Class  Avg    Status  Actions      

      John D.   Gr 5   72.3    Prom [Override]   
      Jane S.   Gr 5   38.1    Rep  [Override]   
      Ali M.    Gr 5          Pend [Override]   

                              [Export CSV] [Finalize Run]

```

---

## Integration Points

### 1. Existing `RulesEngine` service

`PromotionEngineService::resolveNextClass()` calls `RulesEngine::getNextClassInOrder()` as a fallback when no `ClassProgression` entry exists. No changes to `RulesEngine` are required.

### 2. Existing `TermSetupController`

The existing `autoPromote()` method in `TermSetupController` is **not removed**  it remains as a legacy simple promotion path. The new engine is additive. The `promotion_mode` setting in `settings` determines which path the dashboard recommends, but both remain accessible.

### 3. Existing `PromotionController`

The existing manual per-class promotion remains unchanged. The new engine is a separate, parallel feature accessible only to `super_admin`.

### 4. `settings` table

The engine reads these keys from `settings`:

| Key | Default | Used for |
|-----|---------|---------|
| `promotion_mode` | `manual` | Whether to show auto-run prompt |
| `custom_pass_mark` | `50` | Threshold for `max_failed_subjects` rule |
| `calculation_basis` | `term` | Whether to average term or semester records |
| `weighted_average` | `0` | Whether to apply subject weights |

### 5. Menu integration

Add to `resources/views/partials/menu.blade.php` inside the `@if(Qs::userIsSuperAdmin())` block within the Students section:

```blade
@if(Qs::userIsSuperAdmin())
<li class="nav-item">
    <a href="{{ route('promotion_engine.dashboard') }}" 
       class="nav-link {{ str_starts_with(Route::currentRouteName() ?? '', 'promotion_engine.') ? 'active' : '' }}">
        <i class="bi bi-diagram-3 mr-1"></i>Promotion Engine
    </a>
</li>
@endif
```

### 6. Route registration

Add to `routes/web.php` inside the `super_admin` group:

```php
Route::prefix('promotion-engine')->name('promotion_engine.')->group(function () {
    Route::get('/',                              'PromotionEngineController@dashboard')->name('dashboard');
    Route::get('/rules',                         'PromotionEngineController@rules')->name('rules');
    Route::post('/rules',                        'PromotionEngineController@storeRule')->name('rules.store');
    Route::put('/rules/{id}',                    'PromotionEngineController@updateRule')->name('rules.update');
    Route::delete('/rules/{id}',                 'PromotionEngineController@destroyRule')->name('rules.destroy');
    Route::get('/progressions',                  'PromotionEngineController@progressions')->name('progressions');
    Route::post('/progressions',                 'PromotionEngineController@storeProgression')->name('progressions.store');
    Route::put('/progressions/{id}',             'PromotionEngineController@updateProgression')->name('progressions.update');
    Route::delete('/progressions/{id}',          'PromotionEngineController@destroyProgression')->name('progressions.destroy');
    Route::post('/run',                          'PromotionEngineController@initiateRun')->name('run');
    Route::get('/preview/{run_id}',              'PromotionEngineController@preview')->name('preview');
    Route::post('/preview/{run_id}/override',    'PromotionEngineController@applyOverride')->name('preview.override');
    Route::post('/preview/{run_id}/bulk-override','PromotionEngineController@bulkOverride')->name('preview.bulk_override');
    Route::post('/preview/{run_id}/finalize',    'PromotionEngineController@finalizeRun')->name('preview.finalize');
    Route::get('/history',                       'PromotionEngineController@history')->name('history');
    Route::get('/export/{run_id}',               'PromotionEngineController@export')->name('export');
});
```

---
## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

---

### Property 1: Subject category validation rejects invalid values

*For any* string value assigned to `subject_category`, the Subject model's validation SHALL accept the value if and only if it is one of `core`, `elective`, or `optional`.

**Validates: Requirements 1.1**

---

### Property 2: `isCoreSubject()` is equivalent to category equality

*For any* Subject instance with any `subject_category` value, calling `isCoreSubject()` SHALL return `true` if and only if `subject_category === 'core'`.

**Validates: Requirements 1.5**

---

### Property 3: Class progression validation rejects same-class mappings

*For any* pair `(from_class_id, to_class_id)` where both IDs are equal, the ClassProgression validation SHALL reject the mapping. For any pair where either ID does not exist in `my_classes`, the validation SHALL also reject the mapping.

**Validates: Requirements 2.2**

---

### Property 4: Next-class resolution respects DB mapping over fallback

*For any* class that has a `class_progressions` entry, `resolveNextClass()` SHALL return the mapped `to_class_id`. For any class without a `class_progressions` entry, `resolveNextClass()` SHALL return the result of `RulesEngine::getNextClassInOrder()`.

**Validates: Requirements 2.4**

---

### Property 5: Missing progression path yields Pending Review

*For any* student whose current class has no `class_progressions` entry and for whom `RulesEngine::getNextClassInOrder()` returns `null`, the Promotion_Engine SHALL assign Promotion_Status `Pending Review`.

**Validates: Requirements 2.5**

---

### Property 6: Deactivated rules are excluded from evaluation

*For any* `PromotionRule` with `is_active = 0`, the rule SHALL NOT appear in the collection returned by `getApplicableRules()` for any student, class, or academic year.

**Validates: Requirements 3.5**

---

### Property 7: More specific scope overrides broader scope

*For any* student who has both a school-wide rule and a class-level rule of the same `rule_type`, `getApplicableRules()` SHALL return only the class-level rule for that type (not both).

**Validates: Requirements 3.7**

---

### Property 8: Yearly average equals mean of exam_record averages

*For any* student and academic year with N exam records, `computeYearlyAverage()` SHALL return a value equal to `sum(ave) / N` (rounded to 2 decimal places). If N = 0, it SHALL return `null`.

**Validates: Requirements 4.2**

---

### Property 9: Passing all rules yields Promoted status

*For any* student whose data satisfies every active rule in the applicable rule set, `determineStatus()` SHALL return `'Promoted'`.

**Validates: Requirements 4.8**

---

### Property 10: Failing only conditional rules yields Conditionally Promoted

*For any* student who fails one or more rules where every failing rule has `rule_type = 'conditional_promotion'`, `determineStatus()` SHALL return `'Conditionally Promoted'`.

**Validates: Requirements 4.9**

---

### Property 11: Failing any non-conditional rule yields Repeated

*For any* student who fails at least one rule where that rule's `rule_type` is not `'conditional_promotion'`, `determineStatus()` SHALL return `'Repeated'` (unless the student also has incomplete marks, in which case `Pending Review` takes precedence).

**Validates: Requirements 4.10**

---

### Property 12: Incomplete marks yield Pending Review

*For any* student with fewer `exam_records` rows than the number of exams in the academic year, the Promotion_Engine SHALL assign Promotion_Status `Pending Review` and SHALL NOT assign `Promoted` or `Repeated`.

**Validates: Requirements 4.11**

---

### Property 13: Promotion runs are non-destructive

*For any* promotion run, the count of rows in `student_records`, `marks`, `exam_records`, and `promotions` before the run SHALL be less than or equal to the count after the run (rows may be added but never removed).

**Validates: Requirements 5.5**

---

### Property 14: Finalization creates new student_records rows

*For any* student processed in a finalized promotion run, the student's `student_records` row for the previous session SHALL still exist with its original `my_class_id`, `section_id`, and `session` values after finalization.

**Validates: Requirements 5.6**

---

### Property 15: Override is recorded in the log with actor and reason

*For any* manual override applied by any admin user with any reason string, the resulting `PromotionLog` record SHALL contain the overriding admin's `user_id` in `promoted_by`, the reason string in `override_reason`, and `is_override = 1`.

**Validates: Requirements 6.4**

---

### Property 16: Finalized promotion logs contain all required fields

*For any* finalized promotion decision, the corresponding `PromotionLog` record SHALL have non-null values for `student_id`, `previous_class_id`, `new_class_id`, `academic_year`, `promotion_status`, `promoted_by`, and `promotion_date`.

**Validates: Requirements 7.1**

---

### Property 17: Promotion log count is monotonically non-decreasing

*For any* sequence of promotion operations (runs, overrides, finalizations), the total count of rows in `promotion_logs` SHALL never decrease between any two points in time.

**Validates: Requirements 7.2**

---

### Property 18: Bulk override updates all selected students

*For any* subset of preview records selected for a bulk override action, every selected record SHALL have its effective status updated to the chosen status, and a `PromotionLog` entry SHALL be created for each selected student.

**Validates: Requirements 8.2**

---

### Property 19: custom_pass_mark validation enforces [0, 100] range

*For any* integer value submitted as `custom_pass_mark`, the system SHALL accept the value if and only if it is in the range [0, 100] inclusive.

**Validates: Requirements 9.6**

---

### Property 20: Dashboard status counts sum to total

*For any* set of `PromotionPreview` records for a given run, the sum of per-status counts (Promoted + Conditionally Promoted + Repeated + Pending Review) SHALL equal the total student count, and the sum of their percentages SHALL equal 100%.

**Validates: Requirements 10.1**

---

## Error Handling

### Promotion Run Errors

| Condition | Behavior |
|-----------|----------|
| Academic year not finalized | Abort run, return error: "Academic year {year} has unfinalized exams." |
| No active students found | Abort run, return warning: "No active students found for session {year}." |
| No active rules configured | Proceed but set all students to `Pending Review` with reason "No promotion rules configured." |
| Target class not found | Set student to `Pending Review` with reason "Target class not found." |
| No progression path | Set student to `Pending Review` with reason "No progression path defined." |
| DB transaction failure | Roll back entire run, delete the `PromotionRun` record, return 500 error. |

### Override Errors

| Condition | Behavior |
|-----------|----------|
| Override on finalized run | Return 422: "Cannot override a finalized run." |
| Missing notes on Pending Review override | Return 422: "Notes are required when overriding a Pending Review student." |
| Invalid status value | Return 422 with validation error. |

### Finalization Errors

| Condition | Behavior |
|-----------|----------|
| Run already finalized | Return 422: "This run has already been finalized." |
| Students still in Pending Review | Warn admin but allow finalization; Pending Review students are logged as-is. |
| DB failure during finalization | Roll back all new `student_records` and `promotion_logs` created in this transaction; run status reverts to `previewing`. |

All errors are returned as JSON for AJAX calls and as flash messages for full-page requests, consistent with the existing pattern in this codebase (`flash_danger` / `flash_success`).

---

## Testing Strategy

### Unit Tests

Unit tests cover specific examples, edge cases, and error conditions in `PromotionEngineService`:

- `computeYearlyAverage()` with zero, one, and multiple exam records
- `determineStatus()` with all-pass, all-conditional-fail, mixed-fail, and incomplete-marks scenarios
- `resolveNextClass()` with DB mapping present, DB mapping absent with fallback, and no fallback
- `getApplicableRules()` scope priority with overlapping school/class rules
- `isCoreSubject()` for each of the three category values
- ClassProgression validation: same-class rejection, non-existent class rejection
- `custom_pass_mark` validation: boundary values 0, 100, -1, 101

### Property-Based Tests

Property-based testing is appropriate for this feature because the core logic  rule evaluation, status determination, average computation, and log immutability  consists of pure functions with well-defined input/output behavior and universal properties that hold across a wide input space.

**Library:** [PHPUnit](https://phpunit.de/) with [eris](https://github.com/giorgiosironi/eris) (PHP property-based testing library).

Each property test runs a minimum of **100 iterations**.

Tag format: `Feature: student-promotion-engine, Property {N}: {property_text}`

| Property | Test class | Generator |
|----------|-----------|-----------|
| P1: Category validation | `SubjectCategoryPropertyTest` | Random strings |
| P2: isCoreSubject equivalence | `SubjectCategoryPropertyTest` | Random category values |
| P3: Progression validation | `ClassProgressionPropertyTest` | Random class ID pairs |
| P4: Next-class resolution priority | `NextClassResolutionPropertyTest` | Random class sets with/without DB mappings |
| P5: Missing progression  Pending Review | `NextClassResolutionPropertyTest` | Classes with no mapping |
| P6: Deactivated rules excluded | `RuleEvaluationPropertyTest` | Random rule sets with mixed active/inactive |
| P7: Scope priority | `RuleEvaluationPropertyTest` | Random students with overlapping scope rules |
| P8: Yearly average computation | `YearlyAveragePropertyTest` | Random collections of exam records |
| P9P12: Status determination | `StatusDeterminationPropertyTest` | Random rule result sets |
| P13: Non-destructive runs | `PromotionRunPropertyTest` | Random student sets |
| P14: New student_records created | `PromotionRunPropertyTest` | Random student sets |
| P15: Override log completeness | `OverridePropertyTest` | Random admin/reason pairs |
| P16: Log field completeness | `PromotionLogPropertyTest` | Random finalization scenarios |
| P17: Log monotonicity | `PromotionLogPropertyTest` | Random operation sequences |
| P18: Bulk override completeness | `BulkOverridePropertyTest` | Random student subsets |
| P19: Pass mark validation | `ConfigValidationPropertyTest` | Random integers |
| P20: Dashboard counts sum | `DashboardStatsPropertyTest` | Random preview record sets |

### Integration Tests

Integration tests verify the full HTTP flow with a real SQLite test database:

- `POST /promotion-engine/run`  creates run and previews
- `POST /promotion-engine/preview/{id}/override`  updates preview, records log
- `POST /promotion-engine/preview/{id}/finalize`  creates student_records and logs
- `GET /promotion-engine/export/{id}`  returns valid CSV

### Migration Test

A single smoke test verifies all new tables exist and have the expected columns after running migrations.
