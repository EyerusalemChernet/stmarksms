<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CalendarConflict;
use App\Models\CalendarRule;
use App\Models\Holiday;
use App\Services\AcademicCalendarGeneratorService;
use Illuminate\Http\Request;

class AcademicCalendarController extends Controller
{
    protected AcademicCalendarGeneratorService $generator;

    public function __construct(AcademicCalendarGeneratorService $generator)
    {
        $this->middleware('auth');
        $this->generator = $generator;
    }

    public function index()
    {
        return redirect()->route('calendar.index')->with('open_tab', 'manager');
    }

    public function show(Request $req, $yid)
    {
        $yearId = intval($yid) ?: intval($req->segment(2));
        $year   = AcademicYear::with(['holidays', 'conflicts.event'])->find($yearId);
        if (!$year) {
            return redirect()->route('calendar.index')
                ->with('open_tab', 'manager')
                ->with('flash_danger', "Academic year #$yearId not found.");
        }

        // Include both year-linked events AND manually added events (academic_year_id = NULL)
        $events = \App\Models\CalendarEvent::where(function ($q) use ($yearId) {
                $q->where('academic_year_id', $yearId)
                  ->orWhereNull('academic_year_id');
            })
            ->where('start_date', '>=', $year->start_date)
            ->where('start_date', '<=', $year->end_date)
            ->orderBy('start_date')
            ->get();

        $conflicts = CalendarConflict::where('academic_year_id', $yearId)->with('event')->get();
        return view('pages.academic_calendar.show', compact('year', 'events', 'conflicts'));
    }

    public function destroy(Request $req, $yid)
    {
        $yearId = intval($yid) ?: intval($req->segment(2));
        $year   = AcademicYear::find($yearId);
        if (!$year) {
            return redirect()->route('calendar.index')->with('open_tab', 'manager');
        }
        // Delete all related data
        \App\Models\CalendarEvent::where('academic_year_id', $yearId)->delete();
        \App\Models\Holiday::where('academic_year_id', $yearId)->delete();
        CalendarConflict::where('academic_year_id', $yearId)->delete();
        $year->delete();
        return redirect()->route('calendar.index')
            ->with('open_tab', 'manager')
            ->with('flash_success', "Academic year {$year->name} deleted.");
    }

    public function generate(Request $req)
    {
        $req->validate(['start_year' => 'required|integer|min:2020|max:2050']);
        try {
            $year    = $this->generator->generateAcademicYear((int) $req->start_year);
            $summary = [
                'events'    => count($this->generator->getGeneratedEvents()),
                'holidays'  => count($this->generator->getHolidayDates()),
                'conflicts' => count($this->generator->getConflicts()),
            ];
            return response()->json(['ok' => true, 'msg' => "Academic year {$year->name} generated.", 'year_id' => $year->id, 'summary' => $summary]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'msg' => 'Generation failed: ' . $e->getMessage()], 500);
        }
    }

    public function importHolidays(Request $req, $yid)
    {
        $yearId = intval($yid) ?: intval($req->segment(2));
        $year   = AcademicYear::findOrFail($yearId);
        $this->generator->generateAcademicYear($year->start_date->year);
        return response()->json(['ok' => true, 'msg' => 'Holidays re-imported.']);
    }

    public function resolveConflicts(Request $req, $yid)
    {
        $yearId = intval($yid) ?: intval($req->segment(2));
        AcademicYear::findOrFail($yearId);
        $conflicts = $this->generator->resolveConflicts();
        return response()->json(['ok' => true, 'msg' => count($conflicts) . ' conflicts resolved.', 'conflicts' => $conflicts]);
    }

    public function archive(Request $req, $yid)
    {
        $yearId = intval($yid) ?: intval($req->segment(2));
        $year   = AcademicYear::find($yearId);
        if (!$year) {
            return redirect()->route('calendar.index')->with('open_tab', 'manager');
        }
        $year->update(['status' => 'archived', 'is_current' => false]);
        return redirect()->route('calendar.index')
            ->with('open_tab', 'manager')
            ->with('flash_success', 'Academic year archived.');
    }

    public function activate(Request $req, $yid)
    {
        $yearId = intval($yid) ?: intval($req->segment(2));
        $year   = AcademicYear::find($yearId);
        if (!$year) {
            return redirect()->route('calendar.index')->with('open_tab', 'manager');
        }

        if ($year->is_current) {
            // Deactivate — archive it
            $year->update(['status' => 'archived', 'is_current' => false]);
            $msg = "{$year->name} deactivated and archived.";
        } else {
            // Activate — set as current, archive all others
            AcademicYear::where('is_current', true)->update(['is_current' => false, 'status' => 'archived']);
            $year->update(['status' => 'active', 'is_current' => true]);
            $msg = "{$year->name} is now the active current year.";
        }

        return redirect()->route('calendar.index')
            ->with('open_tab', 'manager')
            ->with('flash_success', $msg);
    }

    public function rulesIndex()
    {
        $rules = CalendarRule::orderBy('sort_order')->get();
        return view('pages.academic_calendar.rules', compact('rules'));
    }

    public function rulesStore(Request $req)
    {
        $req->validate(['name' => 'required|string|max:200', 'rule_type' => 'required|string', 'event_type' => 'required|string', 'rule_value' => 'required|string']);
        $ruleValue = json_decode($req->rule_value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['ok' => false, 'msg' => 'Invalid rule value JSON.'], 422);
        }
        CalendarRule::create(['name' => $req->name, 'rule_type' => $req->rule_type, 'event_type' => $req->event_type, 'rule_value' => $ruleValue, 'color' => $req->color ?? '#4f46e5', 'description' => $req->description, 'sort_order' => $req->sort_order ?? 99, 'is_active' => (bool) $req->is_active]);
        return response()->json(['ok' => true, 'msg' => 'Rule created.']);
    }

    public function rulesUpdate(Request $req, $rid)
    {
        $ruleId = intval($req->input('_rule_id') ?: $rid);
        $rule   = CalendarRule::find($ruleId);
        if (!$rule) {
            return response()->json(['ok' => false, 'msg' => 'Rule #' . $ruleId . ' not found.'], 404);
        }
        $data = $req->only(['name', 'rule_type', 'event_type', 'color', 'description', 'sort_order']);
        $data['is_active'] = (bool) $req->is_active;
        if ($req->has('rule_value')) {
            $rv      = $req->rule_value;
            $decoded = is_array($rv) ? $rv : json_decode($rv, true);
            if (is_array($decoded)) {
                $data['rule_value'] = $decoded;
            }
        }
        $rule->update($data);
        return response()->json(['ok' => true, 'msg' => 'Rule updated.']);
    }

    public function rulesDestroy(Request $req, $rid)
    {
        $ruleId = intval($req->input('_rule_id') ?: $rid);
        $rule   = CalendarRule::find($ruleId);
        if (!$rule) {
            return response()->json(['ok' => false, 'msg' => 'Rule #' . $ruleId . ' not found.'], 404);
        }
        $rule->delete();
        return response()->json(['ok' => true, 'msg' => 'Rule deleted.']);
    }
}
