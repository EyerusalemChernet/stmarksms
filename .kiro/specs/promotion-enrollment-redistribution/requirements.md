# Requirements Document

## Introduction

This document defines requirements for the **Promotion, Enrollment, and Interactive Section Redistribution** module of the St. Mark School Management System (Laravel 8). The module introduces a structured, audit-safe academic lifecycle: students are admitted into yearly enrollment records, promoted by creating new enrollment records (never overwriting historical ones), and redistributed across sections through a draft/staging workspace before any changes are committed to the database. The module coexists with the existing `student_records`, `promotions`, and `promotion_rules` tables to preserve backward compatibility.

---

## Glossary

- **Academic_Year**: A named school year (e.g., "2024-2025") stored in the `academic_years` table with an `is_active` flag.
- **Enrollment**: A single row in the `enrollments` table representing one student's placement in one class and section for one academic year. Enrollments are immutable once finalized.
- **Promotion_Batch**: A record in `promotion_batches` that groups all draft and finalized promotion decisions for a given source class/year → target class/year transition.
- **Promotion_Draft**: A staging row in `promotion_drafts` representing a proposed (not yet committed) section assignment for one student within a Promotion_Batch.
- **Promotion_History**: An audit row in `promotion_history` recording every enrollment creation or rollback action tied to a Promotion_Batch.
- **Redistribution_Pool**: The merged set of all students from every section of the source class who passed promotion eligibility checks.
- **Redistribution_Mode**: The algorithm used to assign students from the Redistribution_Pool to target sections. Valid modes: `keep_same`, `random`, `balanced`, `manual`.
- **Promotion_Workspace**: The interactive browser UI (left panel / center board / right controls) used by admins to review and adjust Promotion_Drafts before finalization.
- **Locked_Student**: A student whose `promotion_drafts.is_locked = true`, preventing automatic reshuffling from changing their assigned section.
- **Capacity_Warning**: A visual indicator (green / yellow / red) on a section card reflecting how close the section is to its configured maximum student count.
- **RulesEngine**: The existing `App\Services\RulesEngine` service that evaluates `promotion_rules` records to determine whether a student is eligible for promotion.
- **Student_Record**: The existing `student_records` table row, retained for backward compatibility with legacy features (marks, attendance, report cards).
- **Section**: A named subdivision of a class (e.g., "8A") stored in the existing `sections` table.
- **My_Class**: A named grade level (e.g., "Grade 8") stored in the existing `my_classes` table.
- **Admin**: An authenticated user with the `teamSA` middleware role who operates the Promotion_Workspace.
- **Promotion_Module**: The collective set of controllers, models, services, migrations, and views introduced by this feature.

---

## Requirements

---

### Requirement 1: Academic Year Management

**User Story:** As an Admin, I want to create and manage academic years, so that all enrollments and promotions are scoped to a specific, named school year.

#### Acceptance Criteria

1. THE Academic_Year_Manager SHALL store each academic year with a unique `year_name` (e.g., "2024-2025"), an `id`, and an `is_active` boolean flag.
2. THE Academic_Year_Manager SHALL enforce that at most one Academic_Year has `is_active = true` at any given time.
3. WHEN an Admin activates a new Academic_Year, THE Academic_Year_Manager SHALL deactivate all other Academic_Year records before setting the selected one as active.
4. WHEN an Admin requests the list of academic years, THE Academic_Year_Manager SHALL return all years ordered by `year_name` descending.
5. IF a `year_name` already exists in the `academic_years` table, THEN THE Academic_Year_Manager SHALL reject the creation request and return a validation error identifying the duplicate.

---

### Requirement 2: Enrollment Record Creation

**User Story:** As an Admin, I want each student's class and section placement to be stored as a yearly enrollment record, so that historical placements are never overwritten.

#### Acceptance Criteria

1. WHEN a new student is admitted, THE Enrollment_Manager SHALL create both a `student_records` row (for backward compatibility) and an `enrollments` row linking the student to the active Academic_Year, a My_Class, a Section, and an `enrollment_status` of `active`.
2. THE Enrollment_Manager SHALL resolve a student's current class and section from the student's most recent `enrollments` row whose `enrollment_status` is `active`, not from the `student_records` table.
3. THE Enrollment_Manager SHALL never update or delete an existing `enrollments` row that has `enrollment_status = active` or `finalized`.
4. WHEN a student's enrollment is superseded by a new enrollment (promotion or transfer), THE Enrollment_Manager SHALL set the old enrollment's `enrollment_status` to `superseded` rather than deleting it.
5. THE Enrollment_Manager SHALL assign a unique `roll_no` within the combination of `academic_year_id`, `class_id`, and `section_id` for each new enrollment.
6. IF a student already has an `active` enrollment for the same Academic_Year and My_Class, THEN THE Enrollment_Manager SHALL reject the duplicate enrollment and return a validation error.

---

### Requirement 3: Promotion Eligibility Evaluation

**User Story:** As an Admin, I want the system to evaluate each student against configured promotion rules before building a promotion pool, so that only eligible students are included in the promotion workflow.

#### Acceptance Criteria

1. WHEN an Admin initiates a promotion for a source My_Class and Academic_Year, THE RulesEngine SHALL evaluate every active `promotion_rules` record applicable to that class and year for each student in that class.
2. THE RulesEngine SHALL classify each student as `passed`, `held`, or `conditional` based on the evaluation result of all applicable rules.
3. WHEN a student satisfies all applicable rules, THE RulesEngine SHALL classify the student as `passed`.
4. WHEN a student fails one or more mandatory rules (rule types: `min_overall_average`, `core_subject_min_score`, `max_failed_subjects`, `min_attendance_rate`, `fee_clearance_required`, `discipline_restriction`), THE RulesEngine SHALL classify the student as `held`.
5. WHEN a student's overall average falls within the `conditional_promotion` rule's threshold range, THE RulesEngine SHALL classify the student as `conditional`.
6. THE RulesEngine SHALL include students classified as `passed` and `conditional` in the Redistribution_Pool and exclude students classified as `held`.
7. WHEN no active promotion rules exist for a class, THE RulesEngine SHALL classify all students in that class as `passed` by default.

---

### Requirement 4: Promotion Batch Initialization

**User Story:** As an Admin, I want to create a promotion batch that captures the source and target class/year, so that all draft decisions are grouped and traceable.

#### Acceptance Criteria

1. WHEN an Admin selects a source Academic_Year, source My_Class, target Academic_Year, and target My_Class, THE Promotion_Module SHALL create a `promotion_batches` row with `status = draft`, recording `from_academic_year_id`, `to_academic_year_id`, `from_class_id`, `to_class_id`, `created_by`, and `created_at`.
2. THE Promotion_Module SHALL prevent creation of a new Promotion_Batch if an existing batch with the same `from_academic_year_id`, `from_class_id`, and `to_academic_year_id` already has `status = draft` or `status = finalized`.
3. WHEN a Promotion_Batch is created, THE Promotion_Module SHALL merge all students from every Section of the source My_Class who are in the Redistribution_Pool into a single pool, regardless of their original section assignment.
4. THE Promotion_Module SHALL record the count of students in the Redistribution_Pool in the Promotion_Batch before generating drafts.
5. WHEN the Redistribution_Pool is empty for a source class, THE Promotion_Module SHALL abort batch creation and return an informational message to the Admin.

---

### Requirement 5: Promotion Draft Generation

**User Story:** As an Admin, I want the system to generate draft section assignments for all pooled students using a chosen redistribution mode, so that I have a starting point to review and adjust before committing.

#### Acceptance Criteria

1. WHEN an Admin selects a Redistribution_Mode and confirms draft generation, THE Promotion_Module SHALL create one `promotion_drafts` row per student in the Redistribution_Pool, linked to the Promotion_Batch.
2. WHERE Redistribution_Mode is `keep_same`, THE Promotion_Module SHALL assign each student's `proposed_section_id` to the Section in the target My_Class whose name matches the student's current section name; IF no matching section name exists in the target class, THE Promotion_Module SHALL assign the student to the first available Section of the target class.
3. WHERE Redistribution_Mode is `random`, THE Promotion_Module SHALL shuffle the Redistribution_Pool and distribute students evenly across all active Sections of the target My_Class, with a maximum variance of one student between the largest and smallest section.
4. WHERE Redistribution_Mode is `balanced`, THE Promotion_Module SHALL distribute students across target Sections while satisfying all of the following sub-constraints in priority order: (a) respect each section's configured capacity limit, (b) maintain gender balance within ±1 student per gender per section, (c) keep sibling pairs in the same section where possible, (d) honor Locked_Student assignments.
5. WHERE Redistribution_Mode is `manual`, THE Promotion_Module SHALL create `promotion_drafts` rows with `proposed_section_id = null`, requiring the Admin to assign every student manually in the Promotion_Workspace before finalization is permitted.
6. THE Promotion_Module SHALL set `is_locked = false` for all newly generated Promotion_Drafts.
7. WHEN draft generation completes, THE Promotion_Module SHALL redirect the Admin to the Promotion_Workspace for the created Promotion_Batch.

---

### Requirement 6: Interactive Promotion Workspace — Left Panel

**User Story:** As an Admin, I want to see a filterable list of all students in the promotion pool on the left side of the workspace, so that I can identify and select students for manual reassignment.

#### Acceptance Criteria

1. THE Promotion_Workspace SHALL display a left panel listing every student in the Redistribution_Pool with the following attributes per row: full name, gender, previous section name, and computed academic score (overall average from the source Academic_Year).
2. WHEN an Admin enters text in the search field, THE Promotion_Workspace SHALL filter the student list in real time to show only students whose name contains the search string, with results updating within 300 ms of the last keystroke.
3. THE Promotion_Workspace SHALL provide filter controls for gender (`male`, `female`, `all`) and previous section, allowing the Admin to narrow the displayed list.
4. WHEN a student is assigned to a section in the center panel, THE Promotion_Workspace SHALL visually distinguish that student in the left panel (e.g., dimmed or tagged) without removing the student from the list.
5. WHEN a student's Promotion_Draft has `is_locked = true`, THE Promotion_Workspace SHALL display a lock icon next to that student's name in the left panel.

---

### Requirement 7: Interactive Promotion Workspace — Center Section Distribution Board

**User Story:** As an Admin, I want to see all target sections as interactive cards with draggable student chips, so that I can visually redistribute students between sections.

#### Acceptance Criteria

1. THE Promotion_Workspace SHALL display a center panel containing one card per active Section of the target My_Class, each card showing the section name, current student count, gender ratio, average academic score, and a capacity usage indicator.
2. THE Promotion_Workspace SHALL render each student assigned to a section as a draggable chip inside the corresponding section card, displaying the student's name and gender icon.
3. WHEN an Admin drags a student chip from one section card and drops it onto another section card, THE Promotion_Workspace SHALL update the student's `proposed_section_id` in the corresponding Promotion_Draft and refresh all live statistics within 200 ms.
4. WHEN a section's student count reaches 80% of its configured capacity, THE Promotion_Workspace SHALL display a yellow Capacity_Warning indicator on that section card.
5. WHEN a section's student count reaches or exceeds 100% of its configured capacity, THE Promotion_Workspace SHALL display a red Capacity_Warning indicator on that section card.
6. WHEN a section's student count is below 80% of its configured capacity, THE Promotion_Workspace SHALL display a green Capacity_Warning indicator on that section card.
7. WHEN an Admin attempts to drop a student chip onto a section that is at 100% capacity, THE Promotion_Workspace SHALL display a warning message and require explicit confirmation before completing the move.
8. WHEN a student chip has `is_locked = true`, THE Promotion_Workspace SHALL render the chip with a lock icon and prevent drag-and-drop repositioning of that chip.

---

### Requirement 8: Interactive Promotion Workspace — Right Control Panel

**User Story:** As an Admin, I want a set of action controls on the right side of the workspace, so that I can manage sections, reshuffle assignments, undo changes, and finalize the promotion.

#### Acceptance Criteria

1. THE Promotion_Workspace SHALL provide an "Add Section" control that, when activated by the Admin, creates a new Section record in the `sections` table for the target My_Class and adds a new empty section card to the center panel immediately.
2. THE Promotion_Workspace SHALL provide a "Remove Section" control that, when activated by the Admin on an empty section card, removes that Section from the center panel and deletes the corresponding `sections` row; IF the section card contains student chips, THE Promotion_Workspace SHALL require the Admin to reassign all students before removal is permitted.
3. THE Promotion_Workspace SHALL provide a "Shuffle Again" control that reruns the current Redistribution_Mode algorithm across all non-locked students, preserving the section assignments of all Locked_Students.
4. THE Promotion_Workspace SHALL provide an "Auto Balance" control that redistributes all non-locked students using the `balanced` Redistribution_Mode algorithm without changing the Redistribution_Mode setting.
5. THE Promotion_Workspace SHALL provide an "Undo" control that reverts the most recent single drag-and-drop or manual assignment action, restoring the previous `proposed_section_id` for the affected student.
6. THE Promotion_Workspace SHALL maintain an undo history of at least 20 consecutive actions within a single workspace session.
7. THE Promotion_Workspace SHALL provide a "Reset" control that restores all Promotion_Drafts in the current batch to the state produced by the last draft generation run, discarding all manual edits made in the current session.
8. THE Promotion_Workspace SHALL provide a "Finalize" control that is enabled only when every student in the Redistribution_Pool has a non-null `proposed_section_id` in their Promotion_Draft.

---

### Requirement 9: Student Locking

**User Story:** As an Admin, I want to lock individual students to their assigned section, so that reshuffling operations do not move them.

#### Acceptance Criteria

1. WHEN an Admin activates the lock control on a student chip in the center panel, THE Promotion_Module SHALL set `promotion_drafts.is_locked = true` for that student's draft row and persist the change immediately.
2. WHEN an Admin deactivates the lock control on a locked student chip, THE Promotion_Module SHALL set `promotion_drafts.is_locked = false` for that student's draft row and persist the change immediately.
3. WHILE a student's Promotion_Draft has `is_locked = true`, THE Promotion_Module SHALL exclude that student from all automatic redistribution operations (Shuffle Again, Auto Balance, balanced mode re-runs).
4. THE Promotion_Module SHALL preserve all lock states when the Admin navigates away from and returns to the Promotion_Workspace for the same Promotion_Batch.

---

### Requirement 10: Promotion Finalization

**User Story:** As an Admin, I want to finalize a promotion batch after reviewing all draft assignments, so that actual enrollment records are created and the academic year transition is committed.

#### Acceptance Criteria

1. WHEN an Admin activates the "Finalize" control and confirms the action in a confirmation dialog, THE Promotion_Module SHALL execute the finalization sequence as a single database transaction.
2. WITHIN the finalization transaction, THE Promotion_Module SHALL create one new `enrollments` row per student in the Redistribution_Pool, setting `academic_year_id` to the target Academic_Year, `class_id` to the target My_Class, `section_id` to the student's `proposed_section_id`, and `enrollment_status` to `active`.
3. WITHIN the finalization transaction, THE Promotion_Module SHALL set the `enrollment_status` of each student's previous active enrollment to `superseded`.
4. WITHIN the finalization transaction, THE Promotion_Module SHALL create one `promotion_history` row per student recording `old_enrollment_id`, `new_enrollment_id`, `action_type = promoted`, and `action_date`.
5. WITHIN the finalization transaction, THE Promotion_Module SHALL update the Promotion_Batch `status` to `finalized` and record `finalized_at`.
6. WITHIN the finalization transaction, THE Promotion_Module SHALL update the corresponding `student_records` row for each student to reflect the new `session`, `my_class_id`, and `section_id`, preserving backward compatibility with existing report card and attendance features.
7. IF any step within the finalization transaction fails, THEN THE Promotion_Module SHALL roll back the entire transaction, leave all existing enrollment records unchanged, and return a descriptive error message to the Admin.
8. WHEN finalization succeeds, THE Promotion_Module SHALL lock the Promotion_Batch against further edits and redirect the Admin to a finalization summary page showing counts of promoted, held, and conditional students.

---

### Requirement 11: Promotion Rollback

**User Story:** As an Admin, I want to roll back a finalized promotion batch, so that incorrectly committed enrollments can be reversed while preserving the audit trail.

#### Acceptance Criteria

1. WHEN an Admin initiates a rollback on a Promotion_Batch with `status = finalized`, THE Promotion_Module SHALL execute the rollback sequence as a single database transaction.
2. WITHIN the rollback transaction, THE Promotion_Module SHALL delete all `enrollments` rows created by the target Promotion_Batch (identified via `promotion_history.new_enrollment_id`).
3. WITHIN the rollback transaction, THE Promotion_Module SHALL restore the `enrollment_status` of each previously `superseded` enrollment (identified via `promotion_history.old_enrollment_id`) back to `active`.
4. WITHIN the rollback transaction, THE Promotion_Module SHALL restore each affected `student_records` row to the `session`, `my_class_id`, and `section_id` values recorded in the corresponding `promotion_history` row.
5. WITHIN the rollback transaction, THE Promotion_Module SHALL update the Promotion_Batch `status` to `rolled_back`.
6. THE Promotion_Module SHALL preserve all `promotion_history` rows after rollback to maintain the audit trail.
7. IF any step within the rollback transaction fails, THEN THE Promotion_Module SHALL roll back the entire transaction and return a descriptive error message to the Admin.
8. WHEN rollback succeeds, THE Promotion_Module SHALL allow the Admin to re-open the Promotion_Batch in draft mode for correction and re-finalization.

---

### Requirement 12: Historical Data Integrity

**User Story:** As an Admin, I want all historical enrollments, report cards, attendance records, and promotion logs to remain intact after any promotion or rollback, so that past academic records are always retrievable.

#### Acceptance Criteria

1. THE Promotion_Module SHALL never delete or overwrite any `enrollments` row with `enrollment_status = finalized` or `superseded`.
2. THE Promotion_Module SHALL never delete any `promotion_history` row regardless of the Promotion_Batch status.
3. THE Promotion_Module SHALL never delete or modify any `marks`, `exam_records`, or attendance records associated with a previous Academic_Year during promotion or rollback operations.
4. WHEN a report card or transcript is generated for a student, THE Enrollment_Manager SHALL resolve the student's class and section for each Academic_Year from the `enrollments` table, ensuring historical accuracy independent of the current `student_records` state.
5. THE Promotion_Module SHALL maintain referential integrity between `promotion_history`, `promotion_drafts`, `promotion_batches`, and `enrollments` using database-level foreign key constraints.

---

### Requirement 13: Backward Compatibility with Existing System

**User Story:** As a developer, I want the new enrollment and promotion tables to coexist with the existing `student_records` and `promotions` tables, so that existing features (marks, attendance, report cards, legacy promotion views) continue to work without modification.

#### Acceptance Criteria

1. THE Promotion_Module SHALL not alter the schema of the existing `student_records`, `promotions`, `promotion_rules`, `my_classes`, `sections`, `marks`, or `exam_records` tables.
2. THE Promotion_Module SHALL keep the existing `PromotionController` and `TermSetupController` functional and accessible via their existing routes.
3. WHEN the new Promotion_Module finalizes a promotion, THE Promotion_Module SHALL also update the corresponding `student_records` row and insert a row into the existing `promotions` table to preserve compatibility with legacy reporting queries.
4. THE Promotion_Module SHALL introduce all new tables (`academic_years`, `enrollments`, `promotion_batches`, `promotion_drafts`, `promotion_history`) via new Laravel migration files without modifying existing migration files.
5. WHERE the existing system reads `student_records.session`, `student_records.my_class_id`, or `student_records.section_id` to determine a student's current placement, THE Promotion_Module SHALL ensure those columns remain accurate after every finalization and rollback operation.

---

### Requirement 14: Promotion Draft Persistence and Session Safety

**User Story:** As an Admin, I want my draft workspace state to be saved automatically, so that I do not lose redistribution progress if I navigate away or the browser session ends.

#### Acceptance Criteria

1. WHEN an Admin makes any change in the Promotion_Workspace (drag-and-drop, lock toggle, manual assignment), THE Promotion_Module SHALL persist the updated `promotion_drafts` row to the database within 2 seconds of the change.
2. WHEN an Admin returns to the Promotion_Workspace for a Promotion_Batch with `status = draft`, THE Promotion_Workspace SHALL restore the exact state of all Promotion_Drafts including section assignments, lock states, and remarks.
3. THE Promotion_Module SHALL allow multiple Promotion_Batches in `draft` status simultaneously, provided each batch targets a different source class or source Academic_Year.
4. WHEN an Admin explicitly activates the "Reset" control, THE Promotion_Module SHALL regenerate all Promotion_Drafts for the batch using the originally selected Redistribution_Mode, discarding all subsequent manual edits.

---

### Requirement 15: Section Capacity Configuration

**User Story:** As an Admin, I want to configure a maximum student capacity for each section, so that the workspace can warn me when a section is overloaded.

#### Acceptance Criteria

1. THE Section_Manager SHALL allow an Admin to set a `capacity` integer value on each Section record in the `sections` table.
2. WHEN a Section has no configured capacity, THE Promotion_Workspace SHALL treat the section as having unlimited capacity and display no Capacity_Warning indicator.
3. THE Promotion_Workspace SHALL compute capacity usage as the ratio of the current student count in the Promotion_Draft to the Section's configured capacity, expressed as a percentage.
4. WHEN capacity usage is computed, THE Promotion_Workspace SHALL update the Capacity_Warning indicator color in real time as students are moved between sections.

---

### Requirement 16: Audit Logging

**User Story:** As an Admin, I want every promotion action to be recorded in an audit log, so that I can trace who made which changes and when.

#### Acceptance Criteria

1. THE Promotion_Module SHALL record a `promotion_history` row for every enrollment created during finalization, capturing `promotion_batch_id`, `student_id`, `old_enrollment_id`, `new_enrollment_id`, `action_type`, and `action_date`.
2. THE Promotion_Module SHALL record a `promotion_history` row for every enrollment deleted or restored during rollback, with `action_type = rolled_back`.
3. THE Promotion_Module SHALL record the `created_by` user ID on every `promotion_batches` row at creation time.
4. WHEN an Admin views the audit log for a Promotion_Batch, THE Promotion_Module SHALL display all associated `promotion_history` rows ordered by `action_date` ascending.
5. THE Promotion_Module SHALL not allow any user to delete or modify `promotion_history` rows through the application interface.
