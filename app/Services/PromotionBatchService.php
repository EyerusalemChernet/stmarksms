<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\MyClass;
use App\Models\PromotionBatch;
use App\Models\PromotionDraft;
use App\Models\PromotionHistory;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Repositories\ExamRepo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PromotionBatchService
{
    public function __construct(
        protected EnrollmentService    $enrollmentService,
        protected RedistributionService $redistributionService
    ) {}

    /**
     * Build the redistribution pool: all students enrolled in the source class/year
     * across ALL sections, merged into one flat collection.
     */
    public function buildPool(int $fromClassId, int $fromYearId): Collection
    {
        $enrollments = Enrollment::where('academic_year_id', $fromYearId)
            ->where('class_id', $fromClassId)
            ->where('enrollment_status', 'active')
            ->with(['student', 'section'])
            ->get();

        if ($enrollments->isNotEmpty()) {
            return $enrollments;
        }

        // Fallback when enrollments are not seeded — use legacy student_records
        $session = AcademicYear::find($fromYearId)?->name ?? Qs::getCurrentSession();

        return StudentRecord::where('my_class_id', $fromClassId)
            ->where('session', $session)
            ->where('grad', 0)
            ->with(['user', 'section'])
            ->get()
            ->map(function ($sr) {
                $row = new \stdClass();
                $row->student_id = $sr->user_id;
                $row->section_id = $sr->section_id;
                $row->student    = $sr->user;
                $row->section    = $sr->section;
                return $row;
            });
    }

    /**
     * Evaluate eligibility for each student using PromotionRule records.
     * Returns collection with eligibility_status and yearly_average added.
     */
    public function evaluateEligibility(Collection $enrollments, int $classId, string $session, ?int $passMark = null): Collection
    {
        $passMark = $passMark ?? (int) (Qs::getSetting('promotion_min_average') ?: Qs::getSetting('custom_pass_mark') ?: 50);

        return $enrollments->map(function ($enrollment) use ($classId, $session, $passMark) {
            $studentId = $enrollment->student_id;
            $avg = ExamRepo::getSessionAverage($studentId, $session);

            $enrollment->yearly_average = $avg;

            if ($avg === null) {
                $enrollment->eligibility_status = 'held'; // no marks = held
            } elseif ($avg >= $passMark) {
                $enrollment->eligibility_status = 'passed';
            } elseif ($avg >= ($passMark - 10)) {
                $enrollment->eligibility_status = 'conditional';
            } else {
                $enrollment->eligibility_status = 'held';
            }

            return $enrollment;
        });
    }

    /**
     * Initialize a promotion batch: validate, create batch, generate drafts.
     */
    public function initBatch(array $params, string $mode, ?int $minAverage = null): PromotionBatch
    {
        $fromYearId  = $params['from_academic_year_id'];
        $fromClassId = $params['from_class_id'];
        $toYearId    = $params['to_academic_year_id'];
        $toClassId   = $params['to_class_id'];

        // Prevent duplicate active/draft batch
        $existing = PromotionBatch::where('from_academic_year_id', $fromYearId)
            ->where('from_class_id', $fromClassId)
            ->where('to_academic_year_id', $toYearId)
            ->whereIn('status', ['draft', 'finalized'])
            ->first();

        if ($existing) {
            throw new \RuntimeException(
                "A {$existing->status} promotion batch already exists for this class/year combination."
            );
        }

        // Build pool
        $fromYear = AcademicYear::findOrFail($fromYearId);
        $pool = $this->buildPool($fromClassId, $fromYearId);

        if ($pool->isEmpty()) {
            throw new \RuntimeException('No active students found for the selected class and academic year.');
        }

        // Evaluate eligibility (all students — promoted and held)
        $pool = $this->evaluateEligibility($pool, $fromClassId, $fromYear->name, $minAverage);

        // Get target sections
        $targetSections = Section::where('my_class_id', $toClassId)->get();

        // Create batch
        $batch = PromotionBatch::create([
            'from_academic_year_id' => $fromYearId,
            'to_academic_year_id'   => $toYearId,
            'from_class_id'         => $fromClassId,
            'to_class_id'           => $toClassId,
            'redistribution_mode'   => $mode,
            'status'                => 'draft',
            'student_count'         => $pool->count(),
            'created_by'            => Auth::id(),
        ]);

        // Generate drafts for entire pool (eligible + held)
        $this->generateDrafts($batch, $pool, $targetSections, $mode);

        return $batch;
    }

    /**
     * Generate promotion_drafts rows for a batch.
     */
    private function generateDrafts(PromotionBatch $batch, Collection $pool, Collection $targetSections, string $mode): void
    {
        $toPromote = $pool->whereIn('eligibility_status', ['passed', 'conditional']);
        $held      = $pool->where('eligibility_status', 'held');

        // Build draft objects for students who will advance
        $drafts = $toPromote->map(function ($enrollment) use ($batch) {
            $draft = new PromotionDraft([
                'promotion_batch_id'  => $batch->id,
                'student_id'          => $enrollment->student_id,
                'current_section_id'  => $enrollment->section_id,
                'proposed_section_id' => null,
                'is_locked'           => false,
                'eligibility_status'  => $enrollment->eligibility_status,
                'yearly_average'      => $enrollment->yearly_average,
            ]);
            $draft->setRelation('student', $enrollment->student);
            $draft->setRelation('currentSection', $enrollment->section);
            return $draft;
        });

        // Apply redistribution for promotable students only
        $distributed = $this->redistributionService->distribute($drafts, $targetSections, $mode);

        // Held students stay in their current section (not promoted to next class)
        $heldRows = $held->map(fn($enrollment) => [
            'promotion_batch_id'  => $batch->id,
            'student_id'          => $enrollment->student_id,
            'current_section_id'  => $enrollment->section_id,
            'proposed_section_id' => $enrollment->section_id,
            'is_locked'           => 1,
            'eligibility_status'  => 'held',
            'yearly_average'      => $enrollment->yearly_average,
            'created_at'          => now(),
            'updated_at'          => now(),
        ])->toArray();

        // Bulk insert
        $now = now();
        $rows = $distributed->map(fn($d) => [
            'promotion_batch_id'  => $batch->id,
            'student_id'          => $d->student_id,
            'current_section_id'  => $d->current_section_id,
            'proposed_section_id' => $d->proposed_section_id,
            'is_locked'           => 0,
            'eligibility_status'  => $d->eligibility_status,
            'yearly_average'      => $d->yearly_average,
            'created_at'          => $now,
            'updated_at'          => $now,
        ])->toArray();

        if (!empty($rows)) {
            DB::table('promotion_drafts')->insert($rows);
        }
        if (!empty($heldRows)) {
            DB::table('promotion_drafts')->insert($heldRows);
        }
    }

    /**
     * Auto-promotion: create draft batches for every class that has a valid next class.
     * Complements manual per-class batch creation — same engine, school-wide run.
     */
    public function createAutoBatchesForAllClasses(int $minAverage, string $mode = 'balanced'): array
    {
        $session  = Qs::getCurrentSession();
        $fromYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::where('name', $session)->first();

        if (!$fromYear) {
            throw new \RuntimeException('No current academic year found. Set up the academic calendar first.');
        }

        $nextSession = Qs::getNextSession();
        $toYear = AcademicYear::where('name', $nextSession)->first()
            ?? AcademicYear::where('is_current', false)->where('name', 'like', '%' . explode('-', $nextSession)[0] . '%')->first();

        if (!$toYear) {
            $parts = explode('-', $session);
            $toYear = AcademicYear::create([
                'name'       => $nextSession,
                'eth_name'   => '',
                'start_date' => Carbon::parse(((int) $parts[0] + 1) . '-09-11'),
                'end_date'   => Carbon::parse(((int) $parts[1] + 1) . '-07-07'),
                'status'     => 'draft',
                'is_current' => false,
            ]);
        }

        $created = 0;
        $skipped = 0;
        $empty   = 0;
        $errors  = [];

        foreach (MyClass::orderBy('name')->get() as $fromClass) {
            $nextName = RulesEngine::getNextClassInOrder($fromClass->name);
            if (!$nextName) {
                continue;
            }

            $toClass = MyClass::where('name', $nextName)->first();
            if (!$toClass) {
                $errors[] = "{$fromClass->name}: next class \"{$nextName}\" not found in system.";
                continue;
            }

            $pool = $this->buildPool($fromClass->id, $fromYear->id);
            if ($pool->isEmpty()) {
                $empty++;
                continue;
            }

            try {
                $this->initBatch([
                    'from_academic_year_id' => $fromYear->id,
                    'to_academic_year_id'   => $toYear->id,
                    'from_class_id'         => $fromClass->id,
                    'to_class_id'           => $toClass->id,
                ], $mode, $minAverage);
                $created++;
            } catch (\RuntimeException $e) {
                if (str_contains($e->getMessage(), 'already exists')) {
                    $skipped++;
                } else {
                    $errors[] = "{$fromClass->name}: {$e->getMessage()}";
                }
            }
        }

        return compact('created', 'skipped', 'empty', 'errors', 'fromYear', 'toYear');
    }

    /**
     * Finalize a promotion batch: create enrollments, supersede old, write history.
     * Wrapped in a single DB transaction.
     */
    public function finalize(PromotionBatch $batch, int $adminId): void
    {
        if (!$batch->isDraft()) {
            throw new \RuntimeException("Only draft batches can be finalized.");
        }

        $toYear = AcademicYear::findOrFail($batch->to_academic_year_id);

        DB::transaction(function () use ($batch, $adminId, $toYear) {
            $drafts = $batch->drafts()->with(['student', 'currentSection'])->get();

            foreach ($drafts as $draft) {
                $isHeld     = $draft->eligibility_status === 'held';
                $targetClassId   = $isHeld ? $batch->from_class_id : $batch->to_class_id;
                $targetSectionId = $draft->proposed_section_id;
                $promoStatus     = $isHeld ? 'D' : 'P';
                $actionType      = $isHeld ? 'held_back' : 'promoted';

                $oldEnrollment = Enrollment::where('student_id', $draft->student_id)
                    ->where('enrollment_status', 'active')
                    ->latest('id')
                    ->first();

                $newEnrollment = Enrollment::create([
                    'student_id'        => $draft->student_id,
                    'academic_year_id'  => $batch->to_academic_year_id,
                    'class_id'          => $targetClassId,
                    'section_id'        => $targetSectionId,
                    'roll_no'           => $this->enrollmentService->nextRollNo(
                        $batch->to_academic_year_id,
                        $targetClassId,
                        $targetSectionId
                    ),
                    'enrollment_status' => 'active',
                ]);

                if ($oldEnrollment) {
                    $this->enrollmentService->supersede($oldEnrollment);
                }

                DB::table('promotion_history')->insert([
                    'promotion_batch_id' => $batch->id,
                    'student_id'         => $draft->student_id,
                    'old_enrollment_id'  => $oldEnrollment?->id,
                    'new_enrollment_id'  => $newEnrollment->id,
                    'old_class_id'       => $oldEnrollment?->class_id ?? $batch->from_class_id,
                    'old_section_id'     => $oldEnrollment?->section_id ?? $draft->current_section_id,
                    'old_session'        => $oldEnrollment?->academicYear?->name ?? $batch->fromYear?->name,
                    'action_type'        => $actionType,
                    'action_date'        => now(),
                    'performed_by'       => $adminId,
                    'created_at'         => now(),
                ]);

                StudentRecord::where('user_id', $draft->student_id)->update([
                    'my_class_id' => $targetClassId,
                    'section_id'  => $targetSectionId,
                    'session'     => $toYear->name,
                ]);

                DB::table('promotions')->insert([
                    'student_id'   => $draft->student_id,
                    'from_class'   => $batch->from_class_id,
                    'from_section' => $draft->current_section_id,
                    'to_class'     => $targetClassId,
                    'to_section'   => $targetSectionId,
                    'from_session' => $batch->fromYear?->name,
                    'to_session'   => $toYear->name,
                    'status'       => $promoStatus,
                    'grad'         => 0,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // Lock the batch
            $batch->update(['status' => 'finalized', 'finalized_at' => now()]);
        });
    }

    /**
     * Rollback a finalized batch: delete new enrollments, restore old ones.
     */
    public function rollback(PromotionBatch $batch, int $adminId): void
    {
        if (!$batch->isFinalized()) {
            throw new \RuntimeException("Only finalized batches can be rolled back.");
        }

        DB::transaction(function () use ($batch, $adminId) {
            $history = DB::table('promotion_history')
                ->where('promotion_batch_id', $batch->id)
                ->where('action_type', 'promoted')
                ->get();

            foreach ($history as $record) {
                // Delete new enrollment
                if ($record->new_enrollment_id) {
                    DB::table('enrollments')->where('id', $record->new_enrollment_id)->delete();
                }

                // Restore old enrollment
                if ($record->old_enrollment_id) {
                    DB::table('enrollments')
                        ->where('id', $record->old_enrollment_id)
                        ->update(['enrollment_status' => 'active', 'updated_at' => now()]);
                }

                // Restore student_records
                if ($record->old_class_id) {
                    StudentRecord::where('user_id', $record->student_id)->update([
                        'my_class_id' => $record->old_class_id,
                        'section_id'  => $record->old_section_id,
                        'session'     => $record->old_session,
                    ]);
                }

                // Write rollback audit entry
                DB::table('promotion_history')->insert([
                    'promotion_batch_id' => $batch->id,
                    'student_id'         => $record->student_id,
                    'old_enrollment_id'  => $record->new_enrollment_id,
                    'new_enrollment_id'  => $record->old_enrollment_id,
                    'action_type'        => 'rolled_back',
                    'action_date'        => now(),
                    'performed_by'       => $adminId,
                    'created_at'         => now(),
                ]);
            }

            $batch->update(['status' => 'rolled_back']);
        });
    }

    /**
     * Regenerate drafts for a batch (Reset action).
     */
    public function regenerateDrafts(PromotionBatch $batch): void
    {
        DB::table('promotion_drafts')->where('promotion_batch_id', $batch->id)->delete();

        $fromYear = AcademicYear::findOrFail($batch->from_academic_year_id);
        $pool = $this->buildPool($batch->from_class_id, $batch->from_academic_year_id);
        $pool = $this->evaluateEligibility($pool, $batch->from_class_id, $fromYear->name);
        $targetSections = Section::where('my_class_id', $batch->to_class_id)->get();

        $this->generateDrafts($batch, $pool, $targetSections, $batch->redistribution_mode);
    }
}
