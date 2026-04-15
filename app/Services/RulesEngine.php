<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\BookRequest;
use App\Models\Exam;
use App\Models\MyClass;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Models\Rule;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\TimeTable;
use Carbon\Carbon;

class RulesEngine
{
    // ─── Helpers ────────────────────────────────────────────────────────────────

    public static function getRules(string $action): \Illuminate\Database\Eloquent\Collection
    {
        return Rule::where('active', true)->where('action', $action)->get();
    }

    public static function getAttendancePercentage(int $student_id, string $year): float
    {
        $sessions = AttendanceSession::where('year', $year)->pluck('id');
        if ($sessions->isEmpty()) return 100.0;
        $total   = AttendanceRecord::whereIn('session_id', $sessions)->where('student_id', $student_id)->count();
        $present = AttendanceRecord::whereIn('session_id', $sessions)->where('student_id', $student_id)->whereIn('status', ['present', 'late'])->count();
        return $total > 0 ? round(($present / $total) * 100, 1) : 100.0;
    }

    public static function hasUnpaidFees(int $student_id): bool
    {
        return PaymentRecord::where('student_id', $student_id)->where('paid', 0)->exists();
    }

    public static function evaluate(Rule $rule, float $actual): bool
    {
        return match ($rule->condition) {
            'lt'  => $actual < $rule->value,
            'lte' => $actual <= $rule->value,
            'gt'  => $actual > $rule->value,
            'gte' => $actual >= $rule->value,
            'eq'  => $actual == $rule->value,
            default => false,
        };
    }

    // ─── Academic Blocks ────────────────────────────────────────────────────────

    public static function isResultBlocked(int $student_id, string $year): bool
    {
        foreach (self::getRules('block_result') as $rule) {
            if ($rule->type === 'attendance_block' && self::evaluate($rule, self::getAttendancePercentage($student_id, $year))) return true;
            if ($rule->type === 'fee_block' && self::hasUnpaidFees($student_id)) return true;
        }
        return false;
    }

    public static function isReportBlocked(int $student_id, string $year): bool
    {
        foreach (self::getRules('block_report') as $rule) {
            if ($rule->type === 'fee_block' && self::hasUnpaidFees($student_id)) return true;
            if ($rule->type === 'attendance_block' && self::evaluate($rule, self::getAttendancePercentage($student_id, $year))) return true;
        }
        return false;
    }

    // ─── PROMOTION VALIDATION ────────────────────────────────────────────────────

    /**
     * Primary school class progression order.
     * Classes are matched by name (case-insensitive, trimmed).
     * Admin can add classes in any order; we sort by this sequence.
     */
    public static function getClassOrder(): array
    {
        return [
            'nursery',
            'lkg', 'ukg',
            'class 1', 'class 2', 'class 3', 'class 4',
            'class 5', 'class 6', 'class 7', 'class 8',
        ];
    }

    /**
     * Get the position of a class in the progression order.
     * Returns -1 if not found (unrecognised class name).
     */
    public static function getClassPosition(string $className): int
    {
        $order = self::getClassOrder();
        $key   = strtolower(trim($className));
        $pos   = array_search($key, $order);
        return $pos !== false ? (int) $pos : -1;
    }

    /**
     * Get the expected next class name after a given class.
     */
    public static function getNextClassInOrder(string $className): ?string
    {
        $order = self::getClassOrder();
        $pos   = self::getClassPosition($className);
        if ($pos === -1 || $pos >= count($order) - 1) return null;
        return ucwords($order[$pos + 1]);
    }

    /**
     * Validate a promotion selection.
     * Returns ['valid' => true] or ['valid' => false, 'message' => '...']
     */
    public static function validatePromotion(int $from_class_id, int $to_class_id): array
    {
        if ($from_class_id === $to_class_id) {
            $cls = MyClass::find($from_class_id);
            $next = self::getNextClassInOrder($cls->name ?? '');
            $hint = $next ? " The only valid promotion is {$next}." : '';
            return [
                'valid'   => false,
                'message' => "Invalid promotion: A student in {$cls->name} cannot be promoted back to {$cls->name}.{$hint}",
            ];
        }

        $from = MyClass::find($from_class_id);
        $to   = MyClass::find($to_class_id);

        if (!$from || !$to) {
            return ['valid' => false, 'message' => 'Invalid class selection.'];
        }

        $fromPos = self::getClassPosition($from->name);
        $toPos   = self::getClassPosition($to->name);

        // Both classes are in the known order — enforce strict sequence
        if ($fromPos !== -1 && $toPos !== -1) {
            if ($toPos <= $fromPos) {
                $next = self::getNextClassInOrder($from->name);
                $hint = $next ? " The only valid promotion is {$next}." : '';
                return [
                    'valid'   => false,
                    'message' => "Invalid promotion: Cannot promote from {$from->name} to {$to->name} because {$to->name} is not a higher class.{$hint}",
                ];
            }
            if (($toPos - $fromPos) > 1) {
                $next = self::getNextClassInOrder($from->name);
                return [
                    'valid'   => false,
                    'message' => "Invalid promotion: Cannot skip classes. A student in {$from->name} must be promoted to {$next} only.",
                ];
            }
            return ['valid' => true];
        }

        // Fallback for custom class names: use alphabetical order
        $allClasses = MyClass::orderBy('name')->get();
        $fromIndex  = $allClasses->search(fn($c) => $c->id === $from_class_id);
        $toIndex    = $allClasses->search(fn($c) => $c->id === $to_class_id);

        if ($toIndex !== false && $fromIndex !== false && $toIndex <= $fromIndex) {
            return [
                'valid'   => false,
                'message' => "Invalid promotion: {$to->name} appears to be the same level or lower than {$from->name}. Please select a higher class.",
            ];
        }

        return ['valid' => true];
    }

    // ─── ATTENDANCE VALIDATION ───────────────────────────────────────────────────

    /**
     * Validate opening an attendance session.
     * Returns ['valid' => true] or ['valid' => false, 'message' => '...']
     */
    public static function validateAttendanceSession(int $class_id, int $section_id, string $date, int $teacher_id): array
    {
        // Future date check
        if (Carbon::parse($date)->isFuture()) {
            return [
                'valid'   => false,
                'message' => 'Attendance cannot be recorded for a future date. Please select today or a past date.',
            ];
        }

        // Duplicate session check
        $exists = AttendanceSession::where([
            'my_class_id' => $class_id,
            'section_id'  => $section_id,
            'date'        => $date,
        ])->exists();

        if ($exists) {
            return [
                'valid'   => false,
                'message' => 'Attendance has already been recorded for this class and date. You can edit the existing session instead.',
            ];
        }

        // Teacher assigned to class check — skip for admins
        $isTeamSA = in_array(
            \App\User::find($teacher_id)->user_type ?? '',
            ['super_admin', 'admin']
        );

        if (!$isTeamSA) {
            // For teachers, check homeroom assignment (section.teacher_id)
            $isHomeroom = \App\Models\Section::where('id', $section_id)
                ->where('teacher_id', $teacher_id)
                ->exists();

            if (!$isHomeroom) {
                $class = MyClass::find($class_id);
                return [
                    'valid'   => false,
                    'message' => "You are not assigned to this class. Only the homeroom teacher can record attendance for this section.",
                ];
            }
        }

        return ['valid' => true];
    }

    // ─── LIBRARY VALIDATION ──────────────────────────────────────────────────────

    /**
     * Validate a book borrow request.
     */
    public static function validateBookBorrow(int $book_id, int $user_id): array
    {
        $book = \App\Models\Book::find($book_id);
        if (!$book) return ['valid' => false, 'message' => 'Book not found.'];

        // No copies available
        $available = $book->total_copies - $book->issued_copies;
        if ($available < 1) {
            return [
                'valid'   => false,
                'message' => "No copies of \"{$book->name}\" are currently available. All {$book->total_copies} copies are issued.",
            ];
        }

        // Student already has this book
        $alreadyHas = BookRequest::where('book_id', $book_id)
            ->where('user_id', $user_id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($alreadyHas) {
            return [
                'valid'   => false,
                'message' => "You already have a pending or active borrow request for \"{$book->name}\".",
            ];
        }

        // Max 3 books at once
        $activeCount = BookRequest::where('user_id', $user_id)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($activeCount >= 3) {
            return [
                'valid'   => false,
                'message' => 'You have reached the maximum limit of 3 borrowed books at a time. Please return a book before borrowing another.',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate a book return.
     */
    public static function validateBookReturn(int $request_id): array
    {
        $br = BookRequest::find($request_id);
        if (!$br) return ['valid' => false, 'message' => 'Borrow record not found.'];

        if ($br->status !== 'approved') {
            return [
                'valid'   => false,
                'message' => 'This book cannot be returned because it was not issued (status: ' . $br->status . ').',
            ];
        }

        return ['valid' => true];
    }

    // ─── PAYMENT VALIDATION ──────────────────────────────────────────────────────

    /**
     * Validate a payment entry.
     */
    public static function validatePayment(int $pr_id, float $amount_to_pay): array
    {
        if ($amount_to_pay <= 0) {
            return [
                'valid'   => false,
                'message' => 'Payment amount must be greater than zero. Negative or zero payments are not allowed.',
            ];
        }

        $pr      = PaymentRecord::with('payment')->find($pr_id);
        if (!$pr) return ['valid' => false, 'message' => 'Payment record not found.'];

        $balance = $pr->payment->amount - $pr->amt_paid;

        if ($amount_to_pay > $balance) {
            return [
                'valid'   => false,
                'message' => "Payment of ₦{$amount_to_pay} exceeds the outstanding balance of ₦{$balance}. Please enter an amount not greater than the balance.",
            ];
        }

        return ['valid' => true];
    }

    // ─── STUDENT ADMISSION VALIDATION ────────────────────────────────────────────

    public static function validateAdmissionNumber(string $admNo, ?int $excludeUserId = null): array
    {
        if (empty(trim($admNo))) {
            return ['valid' => false, 'message' => 'Admission number is required.'];
        }

        $query = StudentRecord::where('adm_no', $admNo);
        if ($excludeUserId) $query->where('user_id', '!=', $excludeUserId);

        if ($query->exists()) {
            return [
                'valid'   => false,
                'message' => "Admission number '{$admNo}' is already assigned to another student. Please use a unique admission number.",
            ];
        }

        return ['valid' => true];
    }

    // ─── CLASS CAPACITY VALIDATION ────────────────────────────────────────────────

    public static function validateClassCapacity(int $class_id, int $section_id): array
    {
        $maxCapacity = 40;
        $rule = Rule::where('active', true)->where('type', 'class_capacity')->first();
        if ($rule) $maxCapacity = (int) $rule->value;

        $current = StudentRecord::where('my_class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('grad', 0)
            ->count();

        if ($current >= $maxCapacity) {
            $class   = MyClass::find($class_id);
            $section = Section::find($section_id);
            return [
                'valid'   => false,
                'message' => "Class {$class->name} ({$section->name}) has reached its maximum capacity of {$maxCapacity} students.",
            ];
        }

        return ['valid' => true];
    }

    // ─── TIMETABLE CONFLICT VALIDATION ───────────────────────────────────────────

    public static function validateTimetableConflict(int $teacher_id, int $class_id, int $slot_id, int $day_index, ?int $excludeId = null): array
    {
        $teacherConflict = \App\Models\TimeTable::where('teacher_id', $teacher_id)
            ->where('time_slot_id', $slot_id)
            ->where('day', $day_index)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($teacherConflict) {
            return [
                'valid'   => false,
                'message' => 'This teacher is already assigned to another class at the same time slot. Please choose a different slot or teacher.',
            ];
        }

        $classConflict = \App\Models\TimeTable::where('my_class_id', $class_id)
            ->where('time_slot_id', $slot_id)
            ->where('day', $day_index)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($classConflict) {
            return [
                'valid'   => false,
                'message' => 'This class already has a subject scheduled at the same time slot. Please choose a different slot.',
            ];
        }

        return ['valid' => true];
    }

    // ─── EXAM SESSION VALIDATION ──────────────────────────────────────────────────

    public static function validateExamSession(string $year): array
    {
        $currentYear  = \App\Helpers\Qs::getCurrentSession();
        $currentParts = explode('-', $currentYear);
        $examParts    = explode('-', $year);

        if (count($examParts) !== 2 || !is_numeric($examParts[0]) || !is_numeric($examParts[1])) {
            return ['valid' => false, 'message' => "Invalid session format '{$year}'. Expected format: YYYY-YYYY (e.g. 2024-2025)."];
        }

        if ((int) $examParts[0] < (int) ($currentParts[0] ?? 0) - 1) {
            return [
                'valid'   => false,
                'message' => "Cannot create an exam for session '{$year}' — this session is too far in the past. Current session is {$currentYear}.",
            ];
        }

        return ['valid' => true];
    }
}
