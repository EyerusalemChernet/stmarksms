# Design Document: Promotion, Enrollment, and Interactive Section Redistribution

## Overview

This module introduces a structured, audit-safe academic lifecycle on top of the existing St. Mark SMS. Students are admitted into yearly `enrollments` records, promoted by creating new enrollment records (never overwriting historical ones), and redistributed across sections through a draft/staging workspace before any changes are committed. The module is fully additive — no existing tables, controllers, or routes are modified.

---

## Architecture

```
HTTP Layer
  PromotionBatchController   (SupportTeam namespace, teamSA)
  PromotionWorkspaceController (SupportTeam namespace, teamSA)
  AcademicYearController     (SuperAdmin namespace, super_admin)

Service Layer
  EnrollmentService          — enrollment CRUD, roll_no generation
  PromotionBatchService      — batch init, draft generation, finalize, rollback
  RedistributionService      — 4 redistribution algorithms

Model Layer
  AcademicYear  Enrollment  PromotionBatch  PromotionDraft  PromotionHistory

Existing (unchanged)
  PromotionController  TermSetupController  RulesEngine  StudentRepo  ExamRepo
```

---

## Database Schema

### 1. `academic_years`

```sql
CREATE TABLE academic_years (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year_name  VARCHAR(20) NOT NULL UNIQUE,
    is_active  TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_active (is_active)
);
```

**Model:** `App\Models\AcademicYear`
**Fillable:** `year_name`, `is_active`
**Methods:** `scopeActive()`, `activate()` (deactivates all others first)

---

### 2. `enrollments`

```sql
CREATE TABLE enrollments (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id        INT UNSIGNED NOT NULL,
    academic_year_id  INT UNSIGNED NOT NULL,
    class_id          INT UNSIGNED NOT NULL,
    section_id        INT UNSIGNED NOT NULL,
    roll_no           VARCHAR(20) NULL,
    enrollment_status ENUM('active','superseded','finalized') NOT NULL DEFAULT 'active',
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,

    UNIQUE KEY uq_student_year (student_id, academic_year_id),
    INDEX idx_status (enrollment_status),
    INDEX idx_class_section (class_id, section_id, academic_year_id),
    FOREIGN KEY (student_id)       REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE RESTRICT,
    FOREIGN KEY (class_id)         REFERENCES my_classes(id) ON DELETE RESTRICT,
    FOREIGN KEY (section_id)       REFERENCES sections(id) ON DELETE RESTRICT
);
```

**Model:** `App\Models\Enrollment`
**Fillable:** `student_id`, `academic_year_id`, `class_id`, `section_id`, `roll_no`, `enrollment_status`
**Relations:** `student()→User`, `academicYear()→AcademicYear`, `myClass()→MyClass`, `section()→Section`
**Scopes:** `scopeActive()`, `scopeForYear(int $yearId)`
**Rule:** `save()` throws if `enrollment_status` is `superseded` or `finalized` (immutability guard)

---

### 3. `promotion_batches`

```sql
CREATE TABLE promotion_batches (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_academic_year_id INT UNSIGNED NOT NULL,
    to_academic_year_id   INT UNSIGNED NOT NULL,
    from_class_id         INT UNSIGNED NOT NULL,
    to_class_id           INT UNSIGNED NOT NULL,
    redistribution_mode   ENUM('keep_same','random','balanced','manual') NOT NULL DEFAULT 'random',
    status                ENUM('draft','finalized','rolled_back') NOT NULL DEFAULT 'draft',
    student_count         INT UNSIGNED NOT NULL DEFAULT 0,
    created_by            INT UNSIGNED NOT NULL,
    finalized_at          TIMESTAMP NULL,
    created_at            TIMESTAMP NULL,
    updated_at            TIMESTAMP NULL,

    INDEX idx_status (status),
    INDEX idx_from (from_academic_year_id, from_class_id),
    FOREIGN KEY (from_academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (to_academic_year_id)   REFERENCES academic_years(id),
    FOREIGN KEY (from_class_id)         REFERENCES my_classes(id),
    FOREIGN KEY (to_class_id)           REFERENCES my_classes(id),
    FOREIGN KEY (created_by)            REFERENCES users(id)
);
```

**Model:** `App\Models\PromotionBatch`
**Fillable:** all except `id`, timestamps
**Relations:** `fromYear()`, `toYear()`, `fromClass()`, `toClass()`, `createdBy()`, `drafts()→hasMany(PromotionDraft)`, `history()→hasMany(PromotionHistory)`
**Scopes:** `scopeDraft()`, `scopeFinalized()`

---

### 4. `promotion_drafts`

```sql
CREATE TABLE promotion_drafts (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promotion_batch_id   INT UNSIGNED NOT NULL,
    student_id           INT UNSIGNED NOT NULL,
    current_section_id   INT UNSIGNED NULL,
    proposed_section_id  INT UNSIGNED NULL,
    is_locked            TINYINT(1) NOT NULL DEFAULT 0,
    redistribution_group VARCHAR(20) NULL,
    eligibility_status   ENUM('passed','held','conditional') NOT NULL DEFAULT 'passed',
    yearly_average       DECIMAL(6,2) NULL,
    remarks              TEXT NULL,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,

    UNIQUE KEY uq_batch_student (promotion_batch_id, student_id),
    INDEX idx_batch_section (promotion_batch_id, proposed_section_id),
    FOREIGN KEY (promotion_batch_id)  REFERENCES promotion_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)          REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_section_id)  REFERENCES sections(id) ON DELETE SET NULL,
    FOREIGN KEY (proposed_section_id) REFERENCES sections(id) ON DELETE SET NULL
);
```

**Model:** `App\Models\PromotionDraft`
**Fillable:** all except `id`, timestamps
**Casts:** `is_locked→boolean`, `yearly_average→float`
**Relations:** `batch()→PromotionBatch`, `student()→User`, `currentSection()→Section`, `proposedSection()→Section`

---

### 5. `promotion_history`

```sql
CREATE TABLE promotion_history (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promotion_batch_id  INT UNSIGNED NOT NULL,
    student_id          INT UNSIGNED NOT NULL,
    old_enrollment_id   INT UNSIGNED NULL,
    new_enrollment_id   INT UNSIGNED NULL,
    old_class_id        INT UNSIGNED NULL,
    old_section_id      INT UNSIGNED NULL,
    old_session         VARCHAR(20) NULL,
    action_type         ENUM('promoted','rolled_back') NOT NULL,
    action_date         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    performed_by        INT UNSIGNED NOT NULL,

    INDEX idx_batch    (promotion_batch_id),
    INDEX idx_student  (student_id),
    FOREIGN KEY (promotion_batch_id) REFERENCES promotion_batches(id),
    FOREIGN KEY (student_id)         REFERENCES users(id),
    FOREIGN KEY (performed_by)       REFERENCES users(id)
);
-- No updated_at — append-only
```

**Model:** `App\Models\PromotionHistory`
**Fillable:** all except `id`
**Rule:** `public $timestamps = false`; `save()` throws on update attempt (append-only guard)

---

## Service Layer

### EnrollmentService

```php
namespace App\Services;

class EnrollmentService
{
    // Create enrollment + student_records row (admission)
    public function createForAdmission(int $studentId, int $classId, int $sectionId, int $yearId): Enrollment;

    // Resolve student's current class from latest active enrollment
    public function currentEnrollment(int $studentId): ?Enrollment;

    // Generate next roll_no for a section/year combination
    public function nextRollNo(int $yearId, int $classId, int $sectionId): string;

    // Mark enrollment as superseded (never deletes)
    public function supersede(Enrollment $enrollment): void;
}
```

### PromotionBatchService

```php
namespace App\Services;

class PromotionBatchService
{
    // Build the redistribution pool from all sections of source class
    public function buildPool(int $fromClassId, int $fromYearId): Collection;

    // Evaluate eligibility for each student using PromotionRule records
    public function evaluateEligibility(Collection $students, int $classId, string $session): Collection;

    // Create batch + drafts in one transaction
    public function initBatch(array $params, string $mode): PromotionBatch;

    // Finalize: create enrollments, supersede old, write history, update student_records
    public function finalize(PromotionBatch $batch, int $adminId): void;

    // Rollback: delete new enrollments, restore superseded, update student_records
    public function rollback(PromotionBatch $batch, int $adminId): void;

    // Regenerate drafts for a batch (Reset action)
    public function regenerateDrafts(PromotionBatch $batch): void;
}
```

### RedistributionService

```php
namespace App\Services;

class RedistributionService
{
    // Assign proposed_section_id to each draft using selected mode
    public function distribute(Collection $drafts, Collection $targetSections, string $mode): Collection;

    // keep_same: match section name in target class
    private function keepSame(Collection $drafts, Collection $targetSections): Collection;

    // random: shuffle + round-robin across sections respecting capacity
    private function random(Collection $drafts, Collection $targetSections): Collection;

    // balanced: gender balance + score balance + capacity + sibling pairs + locked
    private function balanced(Collection $drafts, Collection $targetSections): Collection;

    // manual: set all proposed_section_id = null
    private function manual(Collection $drafts): Collection;

    // Rebalance only unlocked students, preserve locked assignments
    public function rebalanceUnlocked(Collection $drafts, Collection $targetSections): Collection;
}
```

---

## Controller Structure

### PromotionBatchController

**Namespace:** `App\Http\Controllers\SupportTeam`
**Middleware:** `teamSA`

| Method | Route | Action |
|--------|-------|--------|
| GET | `/promotion/batches` | `index()` — list all batches |
| GET | `/promotion/batches/create` | `create()` — setup form |
| POST | `/promotion/batches` | `store()` — init batch + drafts |
| GET | `/promotion/batches/{batch}` | `workspace()` — interactive UI |
| POST | `/promotion/batches/{batch}/shuffle` | `shuffle()` — reshuffle drafts |
| POST | `/promotion/batches/{batch}/finalize` | `finalize()` |
| POST | `/promotion/batches/{batch}/rollback` | `rollback()` |
| DELETE | `/promotion/batches/{batch}` | `destroy()` — delete draft batch |
| GET | `/promotion/batches/{batch}/summary` | `summary()` |

### PromotionWorkspaceController (JSON API for workspace)

| Method | Route | Action |
|--------|-------|--------|
| PATCH | `/promotion/drafts/{draft}` | `updateDraft()` — auto-save section assignment |
| PATCH | `/promotion/drafts/{draft}/lock` | `toggleLock()` |
| POST | `/promotion/sections` | `addSection()` — create section dynamically |
| DELETE | `/promotion/sections/{section}` | `removeSection()` |

### AcademicYearController

**Namespace:** `App\Http\Controllers\SuperAdmin`
**Middleware:** `super_admin`

| Method | Route | Action |
|--------|-------|--------|
| GET | `/super_admin/academic-years` | `index()` |
| POST | `/super_admin/academic-years` | `store()` |
| PATCH | `/super_admin/academic-years/{year}/activate` | `activate()` |
| DELETE | `/super_admin/academic-years/{year}` | `destroy()` |

---

## View Structure

```
resources/views/pages/support_team/promotion/
  batches/
    index.blade.php       — batch list with status badges, create button
    create.blade.php      — setup form: source year/class, target year/class, mode
    workspace.blade.php   — three-panel interactive workspace
    summary.blade.php     — finalization summary (counts, section breakdown)

resources/views/pages/super_admin/
  academic_years/
    index.blade.php       — academic year list + create form
```

---

## Workspace UI Architecture

### Three-Panel Layout

```
┌─────────────────────────────────────────────────────────────────┐
│  Promotion Workspace — Grade 7 → Grade 8 (2024-2025 → 2025-2026)│
├──────────────┬──────────────────────────────┬───────────────────┤
│  LEFT 25%    │       CENTER 50%             │   RIGHT 25%       │
│              │                              │                   │
│  Students    │   Section Distribution       │   Controls        │
│  Pool        │   Board                      │                   │
│              │                              │  [Shuffle Again]  │
│  Search...   │  ┌──────────┐ ┌──────────┐  │  [Auto Balance]   │
│  Filter: ▼   │  │  8A      │ │  8B      │  │  [Undo]           │
│              │  │ 20/40 ●  │ │ 18/40 ●  │  │  [Reset]          │
│  ○ Ali M.    │  │ ♂12 ♀8  │ │ ♂9 ♀9   │  │  [Add Section]    │
│  ● Sara T.   │  │ avg 72%  │ │ avg 68%  │  │  [Remove Section] │
│  ○ John D.   │  │          │ │          │  │                   │
│  ...         │  │ [chip]   │ │ [chip]   │  │  ─────────────    │
│              │  │ [chip]   │ │ [chip]   │  │  Total: 38        │
│              │  └──────────┘ └──────────┘  │  Assigned: 38     │
│              │                              │  Unassigned: 0    │
│              │                              │                   │
│              │                              │  [Finalize ✓]     │
└──────────────┴──────────────────────────────┴───────────────────┘
```

### Alpine.js State Management

```javascript
Alpine.data('promotionWorkspace', () => ({
    batchId: null,
    drafts: {},          // { draftId: { studentId, proposedSectionId, isLocked, ... } }
    sections: {},        // { sectionId: { name, capacity, studentIds: [] } }
    students: {},        // { studentId: { name, gender, score, prevSection, ... } }
    undoStack: [],       // max 20 items: { draftId, prevSectionId, newSectionId }
    searchQuery: '',
    filterGender: 'all',
    filterSection: 'all',
    saving: false,
    saveTimer: null,

    // Move student between sections
    moveStudent(studentId, fromSectionId, toSectionId) {
        const draft = this.getDraftByStudent(studentId);
        if (draft.isLocked) return;
        this.undoStack.push({ draftId: draft.id, prevSectionId: fromSectionId, newSectionId: toSectionId });
        if (this.undoStack.length > 20) this.undoStack.shift();
        draft.proposedSectionId = toSectionId;
        this.updateSectionArrays(studentId, fromSectionId, toSectionId);
        this.scheduleSave(draft.id, toSectionId);
    },

    // Debounced auto-save (500ms)
    scheduleSave(draftId, sectionId) {
        clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(() => this.saveDraft(draftId, sectionId), 500);
    },

    async saveDraft(draftId, sectionId) {
        this.saving = true;
        await fetch(`/promotion/drafts/${draftId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ proposed_section_id: sectionId })
        });
        this.saving = false;
    },

    undo() {
        if (!this.undoStack.length) return;
        const { draftId, prevSectionId, newSectionId } = this.undoStack.pop();
        const draft = this.drafts[draftId];
        draft.proposedSectionId = prevSectionId;
        this.updateSectionArrays(draft.studentId, newSectionId, prevSectionId);
        this.saveDraft(draftId, prevSectionId);
    },

    // Capacity warning color
    capacityColor(sectionId) {
        const s = this.sections[sectionId];
        if (!s.capacity) return 'green';
        const pct = s.studentIds.length / s.capacity;
        if (pct >= 1.0) return 'red';
        if (pct >= 0.8) return 'yellow';
        return 'green';
    },

    // Filtered student list for left panel
    get filteredStudents() {
        return Object.values(this.students).filter(s => {
            const matchName = s.name.toLowerCase().includes(this.searchQuery.toLowerCase());
            const matchGender = this.filterGender === 'all' || s.gender === this.filterGender;
            const matchSection = this.filterSection === 'all' || s.prevSection === this.filterSection;
            return matchName && matchGender && matchSection;
        });
    }
}));
```

### SortableJS Drag-and-Drop

Each section card is a SortableJS group. Students can be dragged between groups. On `onEnd` event, `moveStudent()` is called with the student ID, source section, and target section.

```javascript
sections.forEach(sectionId => {
    new Sortable(document.getElementById(`section-${sectionId}`), {
        group: 'students',
        animation: 150,
        filter: '.locked',  // locked chips are not draggable
        onEnd(evt) {
            const studentId = evt.item.dataset.studentId;
            const fromSection = evt.from.dataset.sectionId;
            const toSection = evt.to.dataset.sectionId;
            if (fromSection !== toSection) {
                workspace.moveStudent(studentId, fromSection, toSection);
            }
        }
    });
});
```

---

## Redistribution Algorithm Pseudocode

### keep_same
```
for each draft in pool:
    targetSection = targetSections.firstWhere('name', draft.currentSection.name)
    if targetSection is null:
        targetSection = targetSections.first()
    draft.proposed_section_id = targetSection.id
```

### random
```
shuffle(pool)
sections = targetSections.sortBy('id')
i = 0
for each draft in pool:
    draft.proposed_section_id = sections[i % sections.count()].id
    i++
```

### balanced
```
// Step 1: honor locked students
locked = pool.where('is_locked', true)
unlocked = pool.where('is_locked', false)

// Step 2: sort unlocked by score desc for even distribution
unlocked = unlocked.sortByDesc('yearly_average')

// Step 3: assign round-robin respecting capacity + gender balance
for each draft in unlocked:
    bestSection = sections
        .filter(s => s.count < s.capacity || s.capacity == null)
        .sortBy(s => abs(s.boys - s.girls))  // prefer gender-balanced
        .sortBy(s => s.count)                // prefer less full
        .first()
    draft.proposed_section_id = bestSection.id
    bestSection.count++
    if draft.student.gender == 'Male': bestSection.boys++
    else: bestSection.girls++
```

### manual
```
for each draft in pool:
    draft.proposed_section_id = null
    draft.is_locked = false
```

---

## Finalization Transaction

```php
DB::transaction(function () use ($batch, $adminId) {
    $drafts = $batch->drafts()->with('student')->get();

    foreach ($drafts as $draft) {
        // 1. Find current active enrollment
        $oldEnrollment = Enrollment::where('student_id', $draft->student_id)
            ->where('enrollment_status', 'active')->latest()->first();

        // 2. Create new enrollment
        $newEnrollment = Enrollment::create([
            'student_id'       => $draft->student_id,
            'academic_year_id' => $batch->to_academic_year_id,
            'class_id'         => $batch->to_class_id,
            'section_id'       => $draft->proposed_section_id,
            'roll_no'          => $this->enrollmentService->nextRollNo(...),
            'enrollment_status'=> 'active',
        ]);

        // 3. Supersede old enrollment (never delete)
        if ($oldEnrollment) {
            DB::table('enrollments')
                ->where('id', $oldEnrollment->id)
                ->update(['enrollment_status' => 'superseded']);
        }

        // 4. Write audit history
        PromotionHistory::create([
            'promotion_batch_id' => $batch->id,
            'student_id'         => $draft->student_id,
            'old_enrollment_id'  => $oldEnrollment?->id,
            'new_enrollment_id'  => $newEnrollment->id,
            'old_class_id'       => $oldEnrollment?->class_id,
            'old_section_id'     => $oldEnrollment?->section_id,
            'old_session'        => $oldEnrollment?->academicYear?->year_name,
            'action_type'        => 'promoted',
            'action_date'        => now(),
            'performed_by'       => $adminId,
        ]);

        // 5. Update student_records for backward compatibility
        StudentRecord::where('user_id', $draft->student_id)->update([
            'my_class_id' => $batch->to_class_id,
            'section_id'  => $draft->proposed_section_id,
            'session'     => $batch->toYear->year_name,
        ]);

        // 6. Insert into legacy promotions table
        DB::table('promotions')->insert([
            'student_id'   => $draft->student_id,
            'from_class'   => $batch->from_class_id,
            'from_section' => $draft->current_section_id,
            'to_class'     => $batch->to_class_id,
            'to_section'   => $draft->proposed_section_id,
            'from_session' => $batch->fromYear->year_name,
            'to_session'   => $batch->toYear->year_name,
            'status'       => 'P',
            'grad'         => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    // 7. Lock the batch
    $batch->update(['status' => 'finalized', 'finalized_at' => now()]);
});
```

## Rollback Transaction

```php
DB::transaction(function () use ($batch, $adminId) {
    $history = PromotionHistory::where('promotion_batch_id', $batch->id)
        ->where('action_type', 'promoted')->get();

    foreach ($history as $record) {
        // 1. Delete new enrollment
        Enrollment::where('id', $record->new_enrollment_id)->delete();

        // 2. Restore old enrollment
        if ($record->old_enrollment_id) {
            DB::table('enrollments')
                ->where('id', $record->old_enrollment_id)
                ->update(['enrollment_status' => 'active']);
        }

        // 3. Restore student_records
        StudentRecord::where('user_id', $record->student_id)->update([
            'my_class_id' => $record->old_class_id,
            'section_id'  => $record->old_section_id,
            'session'     => $record->old_session,
        ]);

        // 4. Write rollback audit entry (preserve original history rows)
        PromotionHistory::create([
            'promotion_batch_id' => $batch->id,
            'student_id'         => $record->student_id,
            'old_enrollment_id'  => $record->new_enrollment_id,
            'new_enrollment_id'  => $record->old_enrollment_id,
            'action_type'        => 'rolled_back',
            'action_date'        => now(),
            'performed_by'       => $adminId,
        ]);
    }

    $batch->update(['status' => 'rolled_back']);
});
```

---

## Correctness Properties

1. **Enrollment immutability** — For any enrollment with `status = superseded` or `finalized`, no UPDATE or DELETE operation shall change its `class_id`, `section_id`, or `academic_year_id`.
2. **One active enrollment per student per year** — For any student and academic year, at most one `enrollments` row shall have `enrollment_status = active`.
3. **Finalization creates new rows only** — The count of `enrollments` rows before finalization shall be strictly less than the count after finalization (rows added, never removed during finalize).
4. **Rollback restores exactly** — After rollback, every student's `student_records.my_class_id` and `student_records.section_id` shall equal the values stored in `promotion_history.old_class_id` and `promotion_history.old_section_id`.
5. **History is append-only** — The count of `promotion_history` rows shall never decrease between any two points in time.
6. **Locked students are never moved by auto-redistribution** — For any draft with `is_locked = true`, its `proposed_section_id` shall remain unchanged after any Shuffle, Auto Balance, or balanced-mode operation.
7. **Undo restores previous state** — After calling undo, the affected draft's `proposed_section_id` shall equal the value it had before the undone action.
8. **Capacity warning accuracy** — For any section with a configured capacity, the displayed warning color shall be green iff usage < 80%, yellow iff 80% ≤ usage < 100%, red iff usage ≥ 100%.
9. **Random mode variance ≤ 1** — After random redistribution, the difference between the largest and smallest section student count shall be at most 1.
10. **Balanced mode gender balance** — After balanced redistribution, for any two sections, the absolute difference in male count and female count shall each be at most 1.
11. **Manual mode all null** — After manual mode draft generation, every draft's `proposed_section_id` shall be null.
12. **Finalize blocked when unassigned** — The Finalize button shall be disabled as long as any draft in the batch has `proposed_section_id = null`.
13. **Batch uniqueness** — No two batches with the same `from_academic_year_id` and `from_class_id` shall have `status = draft` or `status = finalized` simultaneously.
14. **Auto-save within 2 seconds** — Every drag-and-drop or lock toggle shall trigger a PATCH request that completes within 2 seconds.
15. **Undo stack bounded** — The undo stack shall never contain more than 20 entries.
16. **Roll_no uniqueness** — For any combination of `academic_year_id`, `class_id`, and `section_id`, no two `enrollments` rows shall share the same `roll_no`.
17. **Academic year single active** — At most one `academic_years` row shall have `is_active = true` at any time.
18. **Pool merges all sections** — The Redistribution_Pool for a source class shall contain students from every section of that class, not just one section.
19. **Backward compatibility** — After finalization, every affected `student_records` row shall have `session`, `my_class_id`, and `section_id` matching the new enrollment.
20. **Transaction atomicity** — If any step in finalization or rollback fails, zero rows shall be created, updated, or deleted in `enrollments`, `student_records`, `promotions`, or `promotion_history` for that operation.

---

## Testing Strategy

### Unit Tests
- `RedistributionService`: all 4 modes with edge cases (empty pool, single section, capacity overflow)
- `EnrollmentService`: roll_no generation uniqueness, immutability guard
- `PromotionBatchService`: pool building, eligibility evaluation, finalize/rollback logic
- `AcademicYear::activate()`: deactivates all others

### Property-Based Tests (eris, 100+ iterations)
- Properties 1–20 above, each as a property test with random student/section inputs

### Feature (Integration) Tests
- `POST /promotion/batches` → creates batch + drafts
- `PATCH /promotion/drafts/{id}` → persists section assignment
- `POST /promotion/batches/{id}/finalize` → creates enrollments, updates student_records
- `POST /promotion/batches/{id}/rollback` → restores previous state
- Workspace renders with correct student chips and section cards

### Browser Tests (optional)
- Drag-and-drop moves chip and updates stats
- Capacity warning changes color at 80% and 100%
- Undo reverts last move
