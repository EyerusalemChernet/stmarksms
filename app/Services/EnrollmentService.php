<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnrollmentService
{
    /**
     * Create an enrollment row when a student is admitted.
     * Also works as the first enrollment for a new student.
     */
    public function createForAdmission(int $studentId, int $classId, int $sectionId, int $yearId): Enrollment
    {
        return Enrollment::create([
            'student_id'        => $studentId,
            'academic_year_id'  => $yearId,
            'class_id'          => $classId,
            'section_id'        => $sectionId,
            'roll_no'           => $this->nextRollNo($yearId, $classId, $sectionId),
            'enrollment_status' => 'active',
        ]);
    }

    /**
     * Resolve the student's current active enrollment.
     */
    public function currentEnrollment(int $studentId): ?Enrollment
    {
        return Enrollment::where('student_id', $studentId)
            ->where('enrollment_status', 'active')
            ->latest('id')
            ->first();
    }

    /**
     * Generate the next sequential roll_no for a section/year combination.
     * Format: {classId}-{sectionId}-{yearId}-{seq:03d}
     */
    public function nextRollNo(int $yearId, int $classId, int $sectionId): string
    {
        $max = Enrollment::where('academic_year_id', $yearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->whereNotNull('roll_no')
            ->count();

        return str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Mark an enrollment as superseded (never deletes).
     * Uses DB::table() to bypass the model immutability guard.
     */
    public function supersede(Enrollment $enrollment): void
    {
        DB::table('enrollments')
            ->where('id', $enrollment->id)
            ->update(['enrollment_status' => 'superseded', 'updated_at' => now()]);
    }

    /**
     * Get the active academic year ID, falling back to settings.current_session.
     */
    public function activeYearId(): ?int
    {
        $year = AcademicYear::where('is_active', 1)->orWhere('is_current', 1)->first();
        if ($year) return $year->id;

        // Fallback: match by name from settings
        $session = \App\Helpers\Qs::getSetting('current_session');
        if ($session) {
            $year = AcademicYear::where('name', $session)->first();
            return $year?->id;
        }
        return null;
    }

    /**
     * Pick the section in a class with the fewest active students (load balancing).
     */
    public function assignAvailableSection(int $classId): Section
    {
        $sections = Section::where('my_class_id', $classId)->orderBy('id')->get();
        if ($sections->isEmpty()) {
            throw new RuntimeException('No sections are defined for this class. Add sections under Administration → Sections first.');
        }

        $yearId  = $this->activeYearId();
        $session = \App\Helpers\Qs::getSetting('current_session');

        $counts = [];
        foreach ($sections as $sec) {
            $enrollCount = $yearId
                ? Enrollment::where('class_id', $classId)
                    ->where('section_id', $sec->id)
                    ->where('academic_year_id', $yearId)
                    ->where('enrollment_status', 'active')
                    ->count()
                : 0;
            $legacyCount = StudentRecord::where('my_class_id', $classId)
                ->where('section_id', $sec->id)
                ->where('session', $session)
                ->where('grad', 0)
                ->count();
            $counts[$sec->id] = max($enrollCount, $legacyCount);
        }

        $minCount = min($counts);
        $sectionId = collect($counts)->filter(fn ($c) => $c === $minCount)->keys()->first();

        return $sections->firstWhere('id', $sectionId);
    }
}
