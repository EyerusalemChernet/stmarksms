# Requirements Document

## Introduction

The Student Promotion Engine is a configurable, rule-driven promotion system for the existing Laravel 8 School Management System (SMS). It replaces the current hardcoded average-based auto-promotion in `TermSetupController` and the manual per-class promotion in `PromotionController` with a flexible engine that evaluates multiple configurable rules per student, produces a preview for admin review, and permanently records every promotion decision. The engine must never overwrite historical enrollment, marks, or promotion records.

---

## Glossary

- **Promotion_Engine**: The subsystem responsible for evaluating promotion rules and determining each student's promotion outcome.
- **Promotion_Rule**: A single configurable condition (e.g., minimum average, attendance threshold) that the Promotion_Engine evaluates against a student's data.
- **Rule_Set**: The collection of active Promotion_Rules that apply to a given scope (academic year, grade level, department, or entire school).
- **Promotion_Scope**: The target of a Rule_Set — one of: entire school, grade level, department, or academic year.
- **Subject_Category**: A classification applied to a subject — one of: Core Subject, Elective Subject, or Optional Subject.
- **Core_Subject**: A subject marked as critical (e.g., Mathematics, English, Science) whose individual pass mark is enforced by promotion rules.
- **Academic_Year**: The school year identifier in `YYYY-YYYY` format (e.g., `2024-2025`), matching the existing `year` / `session` fields.
- **Class_Progression**: An admin-defined mapping from one `MyClass` to its successor `MyClass` (e.g., Grade 1 → Grade 2).
- **Promotion_Status**: The outcome assigned to a student after rule evaluation — one of: `Promoted`, `Conditionally Promoted`, `Repeated`, or `Pending Review`.
- **Promotion_Preview**: A read-only summary page showing each student's computed Promotion_Status and rule results before the admin commits the promotion run.
- **Promotion_Run**: A single execution of the Promotion_Engine for a given scope and Academic_Year, producing a set of Promotion_Preview records.
- **Manual_Override**: An admin action that changes a student's Promotion_Status after preview, bypassing rule evaluation.
- **Promotion_Log**: An immutable audit record created for every finalized promotion decision, storing the student, classes, status, actor, date, and notes.
- **Fee_Clearance**: The state in which a student has no outstanding unpaid fee records in `payment_records`.
- **Attendance_Rate**: The percentage of attendance sessions a student was present or late, calculated from `attendance_records` and `attendance_sessions` for the given Academic_Year.
- **Yearly_Average**: The mean of a student's `ave` values across all `exam_records` rows for the given Academic_Year.
- **Admin**: A user with `user_type` of `super_admin` or `admin`.
- **Condition_Operator**: A comparison operator used in a Promotion_Rule — one of: `gte`, `lte`, `gt`, `lt`, `eq`.

---

## Requirements

### Requirement 1: Subject Category Classification

**User Story:** As an Admin, I want to classify each subject as Core, Elective, or Optional, so that promotion rules can enforce different pass standards per category.

#### Acceptance Criteria

1. THE Subject_Category system SHALL support exactly three categories: `core`, `elective`, and `optional`.
2. WHEN an Admin saves a subject, THE Subject model SHALL store the subject's category using a `subject_category` column with a default value of `elective`.
3. THE Admin interface SHALL allow the Admin to set or change the Subject_Category for any subject at any time.
4. WHEN a subject's Subject_Category is set to `core`, THE Promotion_Engine SHALL treat that subject's individual score as subject to Core_Subject pass rules during evaluation.
5. THE Subject model SHALL expose a `isCoreSubject()` method that returns `true` when `subject_category` equals `core`.

---

### Requirement 2: Class Progression Mapping

**User Story:** As an Admin, I want to define and customize the progression path from each class to its successor, so that the Promotion_Engine always promotes students to the correct next class.

#### Acceptance Criteria

1. THE Class_Progression system SHALL store a mapping of `from_class_id` → `to_class_id` in a dedicated `class_progressions` table.
2. WHEN an Admin creates or updates a Class_Progression, THE system SHALL validate that `from_class_id` and `to_class_id` are different and that both classes exist in `my_classes`.
3. THE Admin interface SHALL allow the Admin to create, edit, and delete Class_Progression entries.
4. WHEN the Promotion_Engine resolves the next class for a student, THE Promotion_Engine SHALL look up the Class_Progression table first, falling back to the existing `RulesEngine::getNextClassInOrder()` logic only when no mapping exists.
5. IF a Class_Progression entry does not exist for a student's current class and no fallback order is found, THEN THE Promotion_Engine SHALL assign the student a Promotion_Status of `Pending Review` and record the reason as "No progression path defined."
6. THE Class_Progression table SHALL enforce a unique constraint on `from_class_id` so that each class has at most one defined successor.

---

### Requirement 3: Promotion Rule Configuration

**User Story:** As an Admin, I want to create and manage configurable promotion rules, so that the school's promotion policy is enforced automatically without code changes.

#### Acceptance Criteria

1. THE Promotion_Rule system SHALL support the following rule types: `min_overall_average`, `core_subject_min_score`, `max_failed_subjects`, `min_attendance_rate`, `fee_clearance_required`, `discipline_restriction`, and `conditional_promotion`.
2. WHEN an Admin creates a Promotion_Rule, THE system SHALL require: rule name, rule type, Condition_Operator (where applicable), threshold value (where applicable), and Promotion_Scope.
3. THE Promotion_Rule system SHALL support scoping rules to: entire school, a specific grade level (`my_class_id`), a specific department (`department_id`), or a specific Academic_Year.
4. THE Admin interface SHALL provide a "Promotion Rules" page where Admins can create, edit, activate, and deactivate Promotion_Rules.
5. WHEN an Admin deactivates a Promotion_Rule, THE Promotion_Engine SHALL exclude that rule from all future evaluation runs without deleting the rule record.
6. THE Promotion_Rule system SHALL allow multiple rules of different types to be active simultaneously for the same scope.
7. WHEN two active rules of the same type apply to the same student (e.g., a school-wide rule and a grade-level rule), THE Promotion_Engine SHALL apply the more specific scope (grade-level overrides school-wide; department overrides grade-level).

---

### Requirement 4: Promotion Rule Evaluation

**User Story:** As an Admin, I want the Promotion_Engine to automatically evaluate all configured rules for every student, so that promotion decisions are consistent and auditable.

#### Acceptance Criteria

1. WHEN a Promotion_Run is initiated, THE Promotion_Engine SHALL evaluate every active Promotion_Rule in the applicable Rule_Set against each student's data for the given Academic_Year.
2. THE Promotion_Engine SHALL calculate the Yearly_Average for each student as the mean of `ave` values from `exam_records` for all exams in the Academic_Year.
3. WHEN evaluating a `min_overall_average` rule, THE Promotion_Engine SHALL compare the student's Yearly_Average against the rule's threshold using the rule's Condition_Operator.
4. WHEN evaluating a `core_subject_min_score` rule, THE Promotion_Engine SHALL check that every Core_Subject's `cum_ave` in `marks` for the Academic_Year meets the rule's threshold.
5. WHEN evaluating a `max_failed_subjects` rule, THE Promotion_Engine SHALL count the number of subjects where `cum_ave` is below the school's configured pass mark and compare that count against the rule's threshold.
6. WHEN evaluating a `min_attendance_rate` rule, THE Promotion_Engine SHALL use the student's Attendance_Rate for the Academic_Year calculated from `attendance_records`.
7. WHEN evaluating a `fee_clearance_required` rule, THE Promotion_Engine SHALL check whether the student has any unpaid records in `payment_records`.
8. WHEN a student passes all active rules, THE Promotion_Engine SHALL assign Promotion_Status `Promoted`.
9. WHEN a student fails one or more rules but the failing rules are all of type `conditional_promotion`, THE Promotion_Engine SHALL assign Promotion_Status `Conditionally Promoted`.
10. WHEN a student fails one or more non-conditional rules, THE Promotion_Engine SHALL assign Promotion_Status `Repeated`.
11. WHEN a student has incomplete marks (fewer `exam_records` than the number of exams in the Academic_Year), THE Promotion_Engine SHALL assign Promotion_Status `Pending Review` and SHALL NOT assign `Promoted` or `Repeated`.
12. THE Promotion_Engine SHALL record which specific rules each student failed, including the rule name, expected threshold, and actual value observed.

---

### Requirement 5: Data Safety Guards

**User Story:** As an Admin, I want the system to prevent promotion runs when data is incomplete or the academic year is not finalized, so that students are never promoted based on missing or unapproved results.

#### Acceptance Criteria

1. WHEN a Promotion_Run is initiated, THE Promotion_Engine SHALL verify that the Academic_Year exists and is marked as finalized before proceeding.
2. IF the Academic_Year is not finalized, THEN THE Promotion_Engine SHALL abort the run and return an error message identifying the unfinalized year.
3. WHEN a Promotion_Run is initiated, THE Promotion_Engine SHALL verify that the target class for each student exists in `my_classes` before assigning a promotion outcome.
4. IF the target class does not exist, THEN THE Promotion_Engine SHALL assign the student Promotion_Status `Pending Review` and record the reason as "Target class not found."
5. THE Promotion_Engine SHALL never delete or overwrite existing `student_records`, `marks`, `exam_records`, or `promotions` rows when executing a Promotion_Run.
6. WHEN a Promotion_Run is executed, THE Promotion_Engine SHALL create a new `student_records` row for the new Academic_Year rather than updating the existing row.

---

### Requirement 6: Promotion Preview

**User Story:** As an Admin, I want to review a detailed preview of all promotion outcomes before committing them, so that I can catch errors and apply overrides before records are finalized.

#### Acceptance Criteria

1. WHEN a Promotion_Run is initiated, THE Promotion_Engine SHALL generate a Promotion_Preview containing one row per student with: student name, current class, target class, Yearly_Average, number of failed subjects, Attendance_Rate, Fee_Clearance status, computed Promotion_Status, and list of failed rules.
2. THE Promotion_Preview page SHALL allow the Admin to filter students by Promotion_Status, class, and name.
3. THE Promotion_Preview page SHALL allow the Admin to apply a Manual_Override to any individual student, changing the Promotion_Status to `Promoted`, `Repeated`, or `Conditionally Promoted`, with a mandatory notes field.
4. WHEN the Admin applies a Manual_Override, THE system SHALL record the overriding Admin's user ID and the override reason in the Promotion_Log.
5. THE Promotion_Preview page SHALL display a summary count of students in each Promotion_Status category.
6. WHEN the Admin confirms the Promotion_Run from the preview page, THE Promotion_Engine SHALL finalize all promotion decisions and create Promotion_Log records for every student.
7. THE Promotion_Preview SHALL remain accessible (read-only) after finalization for audit purposes.

---

### Requirement 7: Promotion Execution and History

**User Story:** As an Admin, I want every promotion decision to be permanently recorded with full audit details, so that the school has a complete and tamper-proof promotion history.

#### Acceptance Criteria

1. WHEN a promotion decision is finalized, THE Promotion_Engine SHALL create a Promotion_Log record containing: `student_id`, `previous_class_id`, `new_class_id`, `academic_year`, `promotion_status`, `promoted_by` (Admin user ID), `promotion_date`, `override_reason` (nullable), and `notes`.
2. THE Promotion_Log table SHALL be append-only; THE system SHALL never update or delete existing Promotion_Log rows.
3. WHEN a student is promoted, THE Promotion_Engine SHALL update the student's `student_records` row for the new Academic_Year to reflect the new `my_class_id` and `section_id`.
4. WHEN a student is assigned `Repeated`, THE Promotion_Engine SHALL retain the student's existing `my_class_id` and `section_id` in the new Academic_Year record.
5. THE system SHALL provide a Promotion History page where Admins can view all Promotion_Log records, filterable by student name, class, academic year, and Promotion_Status.
6. THE Promotion History page SHALL display: student name, previous class, new class, academic year, Promotion_Status, promoted-by user, promotion date, and notes.

---

### Requirement 8: Manual Override and Bulk Actions

**User Story:** As an Admin, I want to approve, reject, or override individual and bulk student promotions, so that exceptional cases are handled efficiently without re-running the full engine.

#### Acceptance Criteria

1. THE Promotion_Preview page SHALL provide bulk action controls allowing the Admin to select multiple students and apply `Approve`, `Reject`, or `Repeat` to all selected students in a single action.
2. WHEN a bulk action is applied, THE system SHALL apply the selected Promotion_Status to every selected student and record each change in the Promotion_Log with the Admin's user ID.
3. THE Promotion_Preview page SHALL provide a search field and filters for class, Promotion_Status, and student name to assist bulk selection.
4. WHEN an Admin applies a Manual_Override to a student whose Promotion_Status is `Pending Review`, THE system SHALL require the Admin to provide a notes entry before saving.
5. THE system SHALL allow the Admin to export the Promotion_Preview or Promotion_History as a CSV or PDF report.

---

### Requirement 9: Promotion Engine Configuration

**User Story:** As an Admin, I want to configure global promotion settings such as enabling/disabling automatic promotion, choosing term-based or semester-based calculations, and setting weighted averages, so that the engine adapts to the school's academic structure.

#### Acceptance Criteria

1. THE Promotion_Engine configuration SHALL support a `promotion_mode` setting with values `auto` and `manual`, stored in the existing `settings` table.
2. WHEN `promotion_mode` is `manual`, THE Promotion_Engine SHALL only execute when an Admin explicitly initiates a Promotion_Run; THE system SHALL NOT trigger promotion automatically.
3. THE configuration SHALL support a `calculation_basis` setting with values `term` and `semester`, determining whether the Yearly_Average is computed from term-level or semester-level exam records.
4. THE configuration SHALL support a `weighted_average` setting; WHEN enabled, THE Promotion_Engine SHALL apply subject-level weights when computing the Yearly_Average.
5. THE configuration SHALL support a `custom_pass_mark` setting (integer, 0–100) that defines the minimum score for a subject to be considered passed; THE Promotion_Engine SHALL use this value when evaluating `max_failed_subjects` rules.
6. WHEN the Admin saves configuration changes, THE system SHALL validate that `custom_pass_mark` is an integer between 0 and 100 inclusive.
7. THE configuration page SHALL display the current values of all promotion settings and allow the Admin to update them in a single form submission.

---

### Requirement 10: Promotion Statistics Dashboard

**User Story:** As an Admin, I want a promotion statistics dashboard, so that I can quickly understand promotion outcomes across the school for a given academic year.

#### Acceptance Criteria

1. THE Promotion_Engine dashboard SHALL display, for the selected Academic_Year: total students evaluated, count and percentage of students `Promoted`, `Conditionally Promoted`, `Repeated`, and `Pending Review`.
2. THE dashboard SHALL display a per-class breakdown of Promotion_Status counts.
3. WHEN no Promotion_Run has been executed for the selected Academic_Year, THE dashboard SHALL display a message indicating that no promotion data is available.
4. THE dashboard SHALL provide a button to initiate a new Promotion_Run for the selected Academic_Year.
5. THE dashboard SHALL allow the Admin to select any past Academic_Year to view historical promotion statistics.
