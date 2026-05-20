<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\Setting;
use App\Repositories\ExamRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use Illuminate\Http\Request;

class TermSetupController extends Controller
{
    protected $exam, $my_class, $student;

    public function __construct(ExamRepo $exam, MyClassRepo $my_class, StudentRepo $student)
    {
        $this->middleware('teamSA');
        $this->exam      = $exam;
        $this->my_class  = $my_class;
        $this->student   = $student;
    }

    /* ─────────────────────────────────────────────────────────────
     |  TERM & SEMESTER SETUP PAGE
     ───────────────────────────────────────────────────────────── */
    public function index()
    {
        $session = Qs::getSetting('current_session');

        $d['current_session']    = $session;
        $d['semesters_per_year'] = (int) (Qs::getSetting('semesters_per_year') ?: 2);
        $d['terms_per_semester'] = (int) (Qs::getSetting('terms_per_semester') ?: 2);
        $d['promotion_min_avg']  = (int) (Qs::getSetting('promotion_min_average') ?: 50);
        $d['promotion_mode']     = Qs::getSetting('promotion_mode') ?: 'auto';

        // All exams for current session, keyed by term number
        $d['exams'] = Exam::where('year', $session)
                          ->orderBy('term')
                          ->get()
                          ->keyBy('term');

        return view('pages.support_team.exams.term_setup', $d);
    }

    /* ─────────────────────────────────────────────────────────────
     |  SAVE STRUCTURE SETTINGS (semesters, terms, promotion)
     ───────────────────────────────────────────────────────────── */
    public function saveSettings(Request $req)
    {
        $req->validate([
            'semesters_per_year'    => 'required|integer|min:1|max:4',
            'terms_per_semester'    => 'required|integer|min:1|max:4',
            'promotion_min_average' => 'required|integer|min:0|max:100',
            'promotion_mode'        => 'required|in:auto,manual',
        ]);

        $map = [
            'semesters_per_year'   => $req->semesters_per_year,
            'terms_per_semester'   => $req->terms_per_semester,
            'promotion_min_average'=> $req->promotion_min_average,
            'promotion_mode'       => $req->promotion_mode,
        ];

        foreach ($map as $type => $value) {
            Setting::updateOrCreate(['type' => $type], ['description' => $value]);
        }

        return back()->with('flash_success', 'Term & semester settings saved.');
    }

    /* ─────────────────────────────────────────────────────────────
     |  AUTO-PROMOTION
     |  Reads all students, checks their average across all exams
     |  in the current session, promotes/holds based on threshold.
     ───────────────────────────────────────────────────────────── */
    public function autoPromote(Request $req)
    {
        $req->validate([
            'min_average'    => 'required|integer|min:0|max:100',
            'promotion_mode' => 'required|in:auto,manual',
        ]);

        $session    = Qs::getSetting('current_session');
        $oldYrParts = explode('-', $session);
        $newSession = (++$oldYrParts[0]) . '-' . (++$oldYrParts[1]);
        $minAvg     = (int) $req->min_average;

        // Get all exams for this session
        $examIds = Exam::where('year', $session)->pluck('id');
        if ($examIds->isEmpty()) {
            return back()->with('flash_danger', 'No exams found for session ' . $session . '. Create exams first.');
        }

        // Get all active (non-graduated) student records for this session
        $studentRecords = $this->student->getRecord(['session' => $session, 'grad' => 0])->get();

        if ($studentRecords->isEmpty()) {
            return back()->with('flash_danger', 'No active students found for session ' . $session . '.');
        }

        $promoted = 0;
        $held     = 0;
        $skipped  = 0;

        foreach ($studentRecords as $sr) {
            // Calculate average across all exams for this student in this session
            $avgScores = \App\Repositories\ExamRepo::getSessionAverage($sr->user_id, $session);

            if ($avgScores === null) {
                // No marks at all — skip
                $skipped++;
                continue;
            }

            // Determine next class
            $nextClassName = \App\Services\RulesEngine::getNextClassInOrder(
                optional($sr->my_class)->name ?? ''
            );

            $nextClass = $nextClassName
                ? $this->my_class->all()->firstWhere('name', $nextClassName)
                : null;

            // Get first section of next class (or keep same section)
            $nextSection = $nextClass
                ? $this->my_class->getClassSections($nextClass->id)->first()
                : null;

            $isPromoted = $avgScores >= $minAvg;

            $updateData = [
                'session'    => $newSession,
                'my_class_id'=> $isPromoted && $nextClass    ? $nextClass->id    : $sr->my_class_id,
                'section_id' => $isPromoted && $nextSection  ? $nextSection->id  : $sr->section_id,
            ];

            $this->student->updateRecord($sr->id, $updateData);

            // Record in promotions table
            $this->student->createPromotion([
                'from_class'   => $sr->my_class_id,
                'from_section' => $sr->section_id,
                'to_class'     => $updateData['my_class_id'],
                'to_section'   => $updateData['section_id'],
                'student_id'   => $sr->user_id,
                'from_session' => $session,
                'to_session'   => $newSession,
                'grad'         => 0,
                'status'       => $isPromoted ? 'P' : 'D',
            ]);

            $isPromoted ? $promoted++ : $held++;
        }

        $msg = "Auto-promotion complete for session {$session} → {$newSession}. "
             . "Promoted: {$promoted} | Held back: {$held}"
             . ($skipped ? " | Skipped (no marks): {$skipped}" : '');

        return back()->with('flash_success', $msg);
    }
}
