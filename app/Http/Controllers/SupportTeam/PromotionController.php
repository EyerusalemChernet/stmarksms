<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\Services\PromotionInsightService;
use App\Services\RulesEngine;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    protected $my_class, $student;

    public function __construct(MyClassRepo $my_class, StudentRepo $student)
    {
        $this->middleware('teamSA');
        $this->middleware('super_admin')->only(['autoPromotion', 'autoPromote']);

        $this->my_class = $my_class;
        $this->student = $student;
    }

    public function promotion($fc = NULL, $fs = NULL, $tc = NULL, $ts = NULL)
    {
        $d['old_year']   = $old_yr = Qs::getSetting('current_session');
        $old_yr          = explode('-', $old_yr);
        $d['new_year']   = ++$old_yr[0].'-'.++$old_yr[1];
        $d['my_classes'] = $this->my_class->all();
        $d['sections']   = $this->my_class->getAllSections();
        $d['selected']   = false;

        // Build next-class map for the selector auto-suggest
        $d['classNextMap'] = $d['my_classes']->mapWithKeys(function ($cls) {
            return [$cls->id => \App\Services\RulesEngine::getNextClassInOrder($cls->name)];
        })->toArray();

        if ($fc && $fs && $tc && $ts) {
            $d['selected'] = true;
            $d['fc'] = $fc;
            $d['fs'] = $fs;
            $d['tc'] = $tc;
            $d['ts'] = $ts;
            $d['students'] = $sts = $this->student->getRecord(['my_class_id' => $fc, 'section_id' => $fs, 'session' => $d['old_year']])->get();

            if ($sts->count() < 1) {
                return redirect()->route('students.promotion')->with('flash_success', __('msg.nstp'));
            }
        }

        return view('pages.support_team.students.promotion.index', $d);
    }

    public function selector(Request $req)
    {
        $validation = RulesEngine::validatePromotion((int) $req->fc, (int) $req->tc);
        if (!$validation['valid']) {
            return redirect()->route('students.promotion')
                ->with('pop_error', $validation['message']);
        }

        return redirect()->route('students.promotion', [$req->fc, $req->fs, $req->tc, $req->ts]);
    }

    public function promote(Request $req, $fc, $fs, $tc, $ts)
    {
        $oy = Qs::getSetting('current_session'); $d = [];
        $old_yr = explode('-', $oy);
        $ny = ++$old_yr[0].'-'.++$old_yr[1];
        $students = $this->student->getRecord(['my_class_id' => $fc, 'section_id' => $fs, 'session' => $oy ])->get()->sortBy('user.name');

        if($students->count() < 1){
            return redirect()->route('students.promotion')->with('flash_danger', __('msg.srnf'));
        }

        foreach($students as $st){
            $p = 'p-'.$st->id;
            $p = $req->$p;
            if($p === 'P'){ // Promote
                $d['my_class_id'] = $tc;
                $d['section_id'] = $ts;
                $d['session'] = $ny;
            }
            if($p === 'D'){ // Don't Promote
                $d['my_class_id'] = $fc;
                $d['section_id'] = $fs;
                $d['session'] = $ny;
            }
            if($p === 'G'){ // Graduated
                $d['my_class_id'] = $fc;
                $d['section_id'] = $fs;
                $d['grad'] = 1;
                $d['grad_date'] = $oy;
            }

            // Auto-update age from DOB at time of promotion
            if ($st->user && $st->user->dob) {
                try {
                    $d['age'] = \Carbon\Carbon::parse($st->user->dob)->age;
                } catch (\Exception $e) {
                    // keep existing age if DOB is invalid
                }
            }

            $this->student->updateRecord($st->id, $d);

//            Insert New Promotion Data
            $promote['from_class'] = $fc;
            $promote['from_section'] = $fs;
            $promote['grad'] = ($p === 'G') ? 1 : 0;
            $promote['to_class'] = in_array($p, ['D', 'G']) ? $fc : $tc;
            $promote['to_section'] = in_array($p, ['D', 'G']) ? $fs : $ts;
            $promote['student_id'] = $st->user_id;
            $promote['from_session'] = $oy;
            $promote['to_session'] = $ny;
            $promote['status'] = $p;

            $this->student->createPromotion($promote);
        }
        return redirect()->route('students.promotion')->with('flash_success', __('msg.update_ok'));
    }

    /** Redirect legacy URL to unified promotion center. */
    public function autoPromotion()
    {
        return redirect()->route('promotion.batches.index');
    }

    /** Legacy POST URL — runs unified batch auto-promotion. */
    public function autoPromote(Request $req)
    {
        if (!$req->has('redistribution_mode')) {
            $req->merge(['redistribution_mode' => 'balanced']);
        }

        return app(PromotionBatchController::class)->runAuto($req);
    }

    public function manage(Request $req)
    {
        $filters = array_filter([
            'from_class'   => $req->query('fc'),
            'from_section' => $req->query('fs'),
            'status'       => $req->query('status'),
        ]);

        $data['promotions']  = $this->student->getAllPromotions($filters);
        $data['old_year']     = Qs::getCurrentSession();
        $data['new_year']     = Qs::getNextSession();
        $data['my_classes']   = $this->my_class->all();
        $data['sections']     = $this->my_class->getAllSections();
        $data['filters']      = $filters;
        $data['filter_fc']    = $req->query('fc');
        $data['filter_fs']    = $req->query('fs');
        $data['filter_status']= $req->query('status');

        $passMark = (int) (Qs::getSetting('custom_pass_mark') ?: 50);
        $data['insights'] = $data['promotions']->mapWithKeys(function ($p) use ($data, $passMark) {
            return [$p->id => PromotionInsightService::forStudent(
                $p->student_id,
                $data['old_year'],
                $p->status,
                $passMark
            )];
        });

        return view('pages.support_team.students.promotion.reset', $data);
    }

    public function reset($promotion_id)
    {
        $this->reset_single($promotion_id);

        return redirect()->route('students.promotion_manage')->with('flash_success', __('msg.update_ok'));
    }

    public function reset_all()
    {
        $next_session = Qs::getNextSession();
        $where = ['from_session' => Qs::getCurrentSession(), 'to_session' => $next_session];
        $proms = $this->student->getPromotions($where);

        if ($proms->count()){
          foreach ($proms as $prom){
              $this->reset_single($prom->id);

              // Delete Marks if Already Inserted for New Session
              $this->delete_old_marks($prom->student_id, $next_session);
          }
        }

        return Qs::jsonUpdateOk();
    }

    protected function delete_old_marks($student_id, $year)
    {
        Mark::where(['student_id' => $student_id, 'year' => $year])->delete();
    }

    protected function reset_single($promotion_id)
    {
        $prom = $this->student->findPromotion($promotion_id);

        $data['my_class_id'] = $prom->from_class;
        $data['section_id'] = $prom->from_section;
        $data['session'] = $prom->from_session;
        $data['grad'] = 0;
        $data['grad_date'] = null;

        $this->student->update(['user_id' => $prom->student_id], $data);

        return $this->student->deletePromotion($promotion_id);
    }
}
