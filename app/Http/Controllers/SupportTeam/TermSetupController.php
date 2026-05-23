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

}
