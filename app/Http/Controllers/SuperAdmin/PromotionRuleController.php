<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\MyClass;
use App\Models\PromotionRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromotionRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('super_admin');
    }

    public function index()
    {
        $d['rules']       = PromotionRule::with(['scopeClass', 'scopeDepartment', 'createdBy'])
                                ->orderBy('rule_type')->orderBy('scope_type')->get();
        $d['classes']     = MyClass::orderBy('name')->get();
        $d['departments'] = Department::orderBy('name')->get();
        $d['ruleTypes']   = [
            'min_overall_average'    => 'Minimum Overall Average',
            'core_subject_min_score' => 'Core Subject Minimum Score',
            'max_failed_subjects'    => 'Maximum Failed Subjects',
            'min_attendance_rate'    => 'Minimum Attendance Rate',
            'fee_clearance_required' => 'Fee Clearance Required',
            'discipline_restriction' => 'Discipline Restriction',
            'conditional_promotion'  => 'Conditional Promotion',
        ];
        $d['operators'] = ['gte' => '≥ (at least)', 'lte' => '≤ (at most)', 'gt' => '> (more than)', 'lt' => '< (less than)', 'eq' => '= (exactly)'];
        $d['currentYear'] = \App\Helpers\Qs::getSetting('current_session');

        return view('pages.super_admin.promotion_rules.index', $d);
    }

    public function store(Request $req)
    {
        $req->validate([
            'name'                => 'required|string|max:191',
            'rule_type'           => 'required|in:min_overall_average,core_subject_min_score,max_failed_subjects,min_attendance_rate,fee_clearance_required,discipline_restriction,conditional_promotion',
            'condition_operator'  => 'nullable|in:gte,lte,gt,lt,eq',
            'threshold_value'     => 'nullable|numeric|min:0|max:100',
            'scope_type'          => 'required|in:school,class,department,year',
            'scope_class_id'      => 'nullable|exists:my_classes,id',
            'scope_department_id' => 'nullable|exists:departments,id',
            'scope_year'          => 'nullable|string|max:20',
            'description'         => 'nullable|string|max:500',
        ]);

        PromotionRule::create([
            'name'                => $req->name,
            'rule_type'           => $req->rule_type,
            'condition_operator'  => $req->condition_operator,
            'threshold_value'     => $req->threshold_value,
            'scope_type'          => $req->scope_type,
            'scope_class_id'      => $req->scope_type === 'class'      ? $req->scope_class_id      : null,
            'scope_department_id' => $req->scope_type === 'department' ? $req->scope_department_id : null,
            'scope_year'          => $req->scope_type === 'year'       ? $req->scope_year          : null,
            'is_active'           => 1,
            'description'         => $req->description,
            'created_by'          => Auth::id(),
        ]);

        return back()->with('flash_success', 'Promotion rule created.');
    }

    public function update(Request $req, PromotionRule $rule)
    {
        $req->validate([
            'name'                => 'required|string|max:191',
            'condition_operator'  => 'nullable|in:gte,lte,gt,lt,eq',
            'threshold_value'     => 'nullable|numeric|min:0|max:100',
            'scope_type'          => 'required|in:school,class,department,year',
            'scope_class_id'      => 'nullable|exists:my_classes,id',
            'scope_department_id' => 'nullable|exists:departments,id',
            'scope_year'          => 'nullable|string|max:20',
            'description'         => 'nullable|string|max:500',
        ]);

        $rule->update([
            'name'                => $req->name,
            'condition_operator'  => $req->condition_operator,
            'threshold_value'     => $req->threshold_value,
            'scope_type'          => $req->scope_type,
            'scope_class_id'      => $req->scope_type === 'class'      ? $req->scope_class_id      : null,
            'scope_department_id' => $req->scope_type === 'department' ? $req->scope_department_id : null,
            'scope_year'          => $req->scope_type === 'year'       ? $req->scope_year          : null,
            'description'         => $req->description,
        ]);

        return back()->with('flash_success', 'Rule updated.');
    }

    public function toggle(PromotionRule $rule)
    {
        $rule->update(['is_active' => !$rule->is_active]);
        $status = $rule->is_active ? 'activated' : 'deactivated';
        return back()->with('flash_success', "Rule \"{$rule->name}\" {$status}.");
    }

    public function destroy(PromotionRule $rule)
    {
        $rule->delete();
        return back()->with('flash_success', 'Rule deleted.');
    }
}
