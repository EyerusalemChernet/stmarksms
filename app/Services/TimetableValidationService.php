<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\TimeTable;
use App\Models\TimeTableRecord;
use App\User;

class TimetableValidationService
{
    /**
     * Validate a single timetable record and return all conflicts with suggestions.
     *
     * @param  int  $ttrId  TimeTableRecord id
     */
    public function validateTimetable(int $ttrId): array
    {
        $ttr     = TimeTableRecord::with('my_class')->findOrFail($ttrId);
        $entries = TimeTable::where('ttr_id', $ttrId)
            ->with(['subject.teacher', 'time_slot'])
            ->get();

        $conflicts = array_merge(
            $this->detectDoubleBookedSlots($ttr, $entries),
            $this->detectTeacherConflictsAcrossClasses($ttr, $entries),
            $this->detectMissingSubjects($ttr, $entries),
            $this->detectDuplicateSubjectsPerDay($ttr, $entries)
        );

        // Attach a suggested fix to every conflict
        foreach ($conflicts as &$c) {
            $c['suggested_fix'] = $this->suggestFix($c, $entries, $ttr);
        }
        unset($c);

        return [
            'timetable'       => $ttr,
            'total_conflicts' => count($conflicts),
            'conflicts'       => $conflicts,
            'is_valid'        => count($conflicts) === 0,
        ];
    }

    /**
     * Summary health for a timetable (used in list views).
     */
    public function getHealthSummary(int $ttrId): array
    {
        $result   = $this->validateTimetable($ttrId);
        $critical = count(array_filter($result['conflicts'], fn($c) => $c['severity'] === 'critical'));
        $warning  = count(array_filter($result['conflicts'], fn($c) => $c['severity'] === 'warning'));

        return [
            'is_valid'        => $result['is_valid'],
            'critical_count'  => $critical,
            'warning_count'   => $warning,
            'total_conflicts' => $result['total_conflicts'],
            'health'          => $critical > 0 ? 'critical' : ($warning > 0 ? 'warning' : 'good'),
        ];
    }

    // ── Conflict detectors ───────────────────────────────────────────────────

    /**
     * Two subjects scheduled in the same time slot on the same day for this class.
     */
    private function detectDoubleBookedSlots(TimeTableRecord $ttr, $entries): array
    {
        $conflicts = [];
        $seen      = [];   // key → first entry

        foreach ($entries as $entry) {
            // Regular timetable uses 'day'; exam timetable uses 'exam_date'
            $day = $entry->exam_date ?? $entry->day ?? 'unknown';
            $key = $entry->ts_id . '|' . $day;

            if (isset($seen[$key])) {
                $first = $seen[$key];
                $conflicts[] = [
                    'type'      => 'class_double_booked',
                    'severity'  => 'critical',
                    'message'   => sprintf(
                        'Class "%s" has two subjects (%s and %s) in the same time slot on %s.',
                        $ttr->my_class->name ?? '?',
                        $first->subject->name ?? '?',
                        $entry->subject->name ?? '?',
                        $day
                    ),
                    'time_slot' => $entry->time_slot->full ?? '?',
                    'day'       => $day,
                    'entry_1'   => $first,
                    'entry_2'   => $entry,
                ];
            } else {
                $seen[$key] = $entry;
            }
        }

        return $conflicts;
    }

    /**
     * Same teacher assigned to two different timetable records at the same time slot + day.
     * Checks across ALL timetable records in the same year.
     */
    private function detectTeacherConflictsAcrossClasses(TimeTableRecord $ttr, $entries): array
    {
        $conflicts = [];

        // Build a map: teacher_id → [(ts_id, day, ttr_id, subject_name, class_name)]
        // from ALL timetables in the same year
        $allEntries = TimeTable::whereHas('tt_record', fn($q) => $q->where('year', $ttr->year))
            ->where('ttr_id', '!=', $ttr->id)
            ->with(['subject.teacher', 'time_slot', 'tt_record.my_class'])
            ->get();

        foreach ($entries as $entry) {
            $teacherId = $entry->subject->teacher_id ?? null;
            if (!$teacherId) continue;

            $day = $entry->exam_date ?? $entry->day ?? 'unknown';

            // Look for the same teacher in the same slot on the same day in another timetable
            $clash = $allEntries->first(function ($other) use ($teacherId, $entry, $day) {
                $otherDay = $other->exam_date ?? $other->day ?? 'unknown';
                return $other->subject->teacher_id == $teacherId
                    && $other->ts_id == $entry->ts_id
                    && $otherDay == $day;
            });

            if ($clash) {
                $teacher = $entry->subject->teacher;
                $conflicts[] = [
                    'type'      => 'teacher_double_booked',
                    'severity'  => 'critical',
                    'message'   => sprintf(
                        'Teacher "%s" is assigned to two classes (%s and %s) at the same time on %s.',
                        $teacher->name ?? '?',
                        $ttr->my_class->name ?? '?',
                        $clash->tt_record->my_class->name ?? '?',
                        $day
                    ),
                    'time_slot'    => $entry->time_slot->full ?? '?',
                    'day'          => $day,
                    'teacher_name' => $teacher->name ?? '?',
                    'entry_1'      => $entry,
                    'entry_2'      => $clash,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Subjects assigned to this class that have no timetable entry at all.
     */
    private function detectMissingSubjects(TimeTableRecord $ttr, $entries): array
    {
        $conflicts = [];

        $classSubjects    = Subject::where('my_class_id', $ttr->my_class_id)->get();
        $scheduledSubIds  = $entries->pluck('subject_id')->unique();

        foreach ($classSubjects as $sub) {
            if (!$scheduledSubIds->contains($sub->id)) {
                $conflicts[] = [
                    'type'     => 'subject_not_scheduled',
                    'severity' => 'warning',
                    'message'  => sprintf(
                        'Subject "%s" is assigned to class "%s" but has no timetable entry.',
                        $sub->name,
                        $ttr->my_class->name ?? '?'
                    ),
                    'subject'  => $sub,
                    'entry_1'  => null,
                    'entry_2'  => null,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Same subject appearing more than once on the same day (may be intentional but worth flagging).
     */
    private function detectDuplicateSubjectsPerDay(TimeTableRecord $ttr, $entries): array
    {
        $conflicts = [];
        $seen      = [];

        foreach ($entries as $entry) {
            $day = $entry->exam_date ?? $entry->day ?? 'unknown';
            $key = $entry->subject_id . '|' . $day;

            if (isset($seen[$key])) {
                $conflicts[] = [
                    'type'     => 'subject_repeated_same_day',
                    'severity' => 'warning',
                    'message'  => sprintf(
                        'Subject "%s" appears more than once on %s for class "%s".',
                        $entry->subject->name ?? '?',
                        $day,
                        $ttr->my_class->name ?? '?'
                    ),
                    'time_slot' => $entry->time_slot->full ?? '?',
                    'day'       => $day,
                    'entry_1'   => $seen[$key],
                    'entry_2'   => $entry,
                ];
            } else {
                $seen[$key] = $entry;
            }
        }

        return $conflicts;
    }

    // ── Suggestion engine ────────────────────────────────────────────────────

    private function suggestFix(array $conflict, $entries, TimeTableRecord $ttr): string
    {
        return match ($conflict['type']) {
            'class_double_booked' => $this->suggestForDoubleBooked($conflict, $entries),
            'teacher_double_booked' => $this->suggestForTeacherClash($conflict, $entries),
            'subject_not_scheduled' => sprintf(
                'Add "%s" to the timetable. Go to "Add Subject" tab and assign it to an available time slot.',
                $conflict['subject']->name ?? 'this subject'
            ),
            'subject_repeated_same_day' => sprintf(
                'Move one of the "%s" sessions to a different day to distribute the workload.',
                $conflict['entry_1']->subject->name ?? 'this subject'
            ),
            default => 'Review and manually adjust the conflicting entry.',
        };
    }

    private function suggestForDoubleBooked(array $conflict, $entries): string
    {
        // Find time slots in this timetable that have no entry yet
        $usedSlotKeys = $entries->map(fn($e) => ($e->exam_date ?? $e->day ?? '') . '|' . $e->ts_id)
            ->unique()->values();

        $allSlots = TimeSlot::where('ttr_id', $conflict['entry_1']->ttr_id)->get();
        $freeSlots = $allSlots->filter(function ($slot) use ($usedSlotKeys, $conflict) {
            $day = $conflict['day'];
            return !$usedSlotKeys->contains("{$day}|{$slot->id}");
        });

        if ($freeSlots->isNotEmpty()) {
            $names = $freeSlots->take(2)->pluck('full')->implode(' or ');
            return "Move one of the conflicting subjects to a free slot: {$names}.";
        }

        return 'No free slots available on this day. Consider moving one subject to a different day.';
    }

    private function suggestForTeacherClash(array $conflict, $entries): string
    {
        $teacherId = $conflict['entry_1']->subject->teacher_id ?? null;
        $tsId      = $conflict['entry_1']->ts_id;
        $day       = $conflict['day'];

        // Find teachers not busy at this slot across all timetables
        $busyTeacherIds = TimeTable::where('ts_id', $tsId)
            ->whereHas('tt_record', fn($q) => $q->where('year', $conflict['entry_1']->tt_record->year ?? ''))
            ->with('subject')
            ->get()
            ->pluck('subject.teacher_id')
            ->filter()
            ->unique();

        $available = User::where('user_type', 'teacher')
            ->whereNotIn('id', $busyTeacherIds)
            ->take(3)
            ->get();

        if ($available->isNotEmpty()) {
            $names = $available->pluck('name')->implode(', ');
            return "Available teachers at this time: {$names}. Reassign the subject to one of them in Staff → Subjects.";
        }

        // Suggest moving to a different slot
        $freeSlots = TimeSlot::where('ttr_id', $conflict['entry_1']->ttr_id)
            ->where('id', '!=', $tsId)
            ->take(2)
            ->get();

        if ($freeSlots->isNotEmpty()) {
            $slots = $freeSlots->pluck('full')->implode(' or ');
            return "No available teachers at this time. Move one class to a different slot: {$slots}.";
        }

        return 'Reassign one of the conflicting subjects to a different teacher or time slot.';
    }
}
