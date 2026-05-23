<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\MyClass;
use App\Models\PromotionBatch;
use App\Models\PromotionDraft;
use App\Models\Section;
use App\Services\PromotionBatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromotionBatchController extends Controller
{
    public function __construct(protected PromotionBatchService $batchService)
    {
        $this->middleware('teamSA');
    }

    public function index()
    {
        $batches = PromotionBatch::with(['fromYear', 'toYear', 'fromClass', 'toClass', 'createdBy'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $promotion_min_avg = (int) (\App\Helpers\Qs::getSetting('promotion_min_average') ?: 50);
        $promotion_mode    = \App\Helpers\Qs::getSetting('promotion_mode') ?: 'auto';
        $current_session   = \App\Helpers\Qs::getSetting('current_session');
        $draft_count       = PromotionBatch::where('status', 'draft')->count();

        return view('pages.support_team.promotion.batches.index', compact(
            'batches', 'promotion_min_avg', 'promotion_mode', 'current_session', 'draft_count'
        ));
    }

    /**
     * School-wide auto-promotion: evaluate all classes and create draft batches
     * (same engine as manual batch — review in workspace, then finalize).
     */
    public function runAuto(Request $req)
    {
        $req->validate([
            'min_average'         => 'required|integer|min:0|max:100',
            'redistribution_mode' => 'required|in:keep_same,random,balanced,manual',
        ]);

        try {
            $result = $this->batchService->createAutoBatchesForAllClasses(
                (int) $req->min_average,
                $req->redistribution_mode
            );

            $msg = "Auto-evaluation complete: {$result['created']} draft batch(es) created.";
            if ($result['skipped'] > 0) {
                $msg .= " {$result['skipped']} skipped (batch already exists).";
            }
            if ($result['empty'] > 0) {
                $msg .= " {$result['empty']} class(es) had no students.";
            }
            if (!empty($result['errors'])) {
                $msg .= ' Warnings: ' . implode(' ', array_slice($result['errors'], 0, 3));
            }
            $msg .= ' Open each batch in the workspace to adjust sections, then finalize.';

            return redirect()->route('promotion.batches.index')->with('flash_success', $msg);
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        }
    }

    public function create()
    {
        $years   = AcademicYear::orderByDesc('name')->get();
        $classes = MyClass::orderBy('name')->get();

        return view('pages.support_team.promotion.batches.create', compact('years', 'classes'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id'   => 'required|exists:academic_years,id',
            'from_class_id'         => 'required|exists:my_classes,id',
            'to_class_id'           => 'required|exists:my_classes,id',
            'redistribution_mode'   => 'required|in:keep_same,random,balanced,manual',
        ]);

        try {
            $batch = $this->batchService->initBatch($req->only([
                'from_academic_year_id', 'to_academic_year_id',
                'from_class_id', 'to_class_id',
            ]), $req->redistribution_mode);

            return redirect()->route('promotion.batches.workspace', $batch->id)
                ->with('flash_success', "Promotion batch created with {$batch->student_count} students.");
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('flash_danger', $e->getMessage());
        }
    }

    public function workspace(PromotionBatch $batch)
    {
        $batch->load(['fromYear', 'toYear', 'fromClass', 'toClass']);

        $drafts = PromotionDraft::where('promotion_batch_id', $batch->id)
            ->with(['student', 'currentSection', 'proposedSection'])
            ->get();

        $targetSections = Section::where('my_class_id', $batch->to_class_id)->get();

        // Build JSON data for Alpine.js
        $studentsJson = $drafts->mapWithKeys(fn($d) => [
            $d->student_id => [
                'id'          => $d->student_id,
                'draftId'     => $d->id,
                'name'        => $d->student?->name ?? '—',
                'gender'      => strtolower($d->student?->gender ?? 'male'),
                'score'       => $d->yearly_average,
                'prevSection' => $d->currentSection?->name ?? '—',
                'isLocked'    => (bool) $d->is_locked,
                'sectionId'   => $d->proposed_section_id,
                'status'      => $d->eligibility_status,
            ]
        ]);

        $sectionsJson = $targetSections->mapWithKeys(fn($s) => [
            $s->id => [
                'id'       => $s->id,
                'name'     => $s->name,
                'capacity' => $s->capacity,
                'students' => $drafts->where('proposed_section_id', $s->id)->pluck('student_id')->values(),
            ]
        ]);

        $unassigned = $drafts->whereNull('proposed_section_id')->pluck('student_id')->values();

        return view('pages.support_team.promotion.batches.workspace', compact(
            'batch', 'drafts', 'targetSections', 'studentsJson', 'sectionsJson', 'unassigned'
        ));
    }

    public function shuffle(PromotionBatch $batch)
    {
        if (!$batch->isDraft()) {
            return response()->json(['ok' => false, 'msg' => 'Batch is not in draft status.'], 422);
        }

        try {
            $this->batchService->regenerateDrafts($batch);
            return response()->json(['ok' => true, 'msg' => 'Drafts reshuffled.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function finalize(PromotionBatch $batch)
    {
        // Check all drafts are assigned
        $unassigned = PromotionDraft::where('promotion_batch_id', $batch->id)
            ->whereNull('proposed_section_id')
            ->whereIn('eligibility_status', ['passed', 'conditional'])
            ->count();

        if ($unassigned > 0) {
            return back()->with('flash_danger', "{$unassigned} student(s) still have no section assigned.");
        }

        try {
            $this->batchService->finalize($batch, Auth::id());
            return redirect()->route('promotion.batches.summary', $batch->id)
                ->with('flash_success', 'Promotion finalized successfully.');
        } catch (\Exception $e) {
            return back()->with('flash_danger', 'Finalization failed: ' . $e->getMessage());
        }
    }

    public function rollback(PromotionBatch $batch)
    {
        try {
            $this->batchService->rollback($batch, Auth::id());
            return redirect()->route('promotion.batches.index')
                ->with('flash_success', 'Promotion rolled back successfully.');
        } catch (\Exception $e) {
            return back()->with('flash_danger', 'Rollback failed: ' . $e->getMessage());
        }
    }

    public function destroy(PromotionBatch $batch)
    {
        if (!$batch->isDraft()) {
            return back()->with('flash_danger', 'Only draft batches can be deleted.');
        }
        $batch->drafts()->delete();
        $batch->delete();
        return back()->with('flash_success', 'Draft batch deleted.');
    }

    public function summary(PromotionBatch $batch)
    {
        $batch->load(['fromYear', 'toYear', 'fromClass', 'toClass']);

        $drafts = PromotionDraft::where('promotion_batch_id', $batch->id)
            ->with(['student', 'proposedSection'])
            ->get();

        $bySection = $drafts->whereNotNull('proposed_section_id')
            ->groupBy('proposed_section_id');

        $counts = [
            'promoted'    => $drafts->where('eligibility_status', 'passed')->count(),
            'conditional' => $drafts->where('eligibility_status', 'conditional')->count(),
            'held'        => $drafts->where('eligibility_status', 'held')->count(),
            'total'       => $drafts->count(),
        ];

        return view('pages.support_team.promotion.batches.summary', compact(
            'batch', 'drafts', 'bySection', 'counts'
        ));
    }
}
