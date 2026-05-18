<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\TimeTable;
use App\Repositories\MyClassRepo;
use App\Services\AutoTimetableGeneratorService;
use Illuminate\Http\Request;

class AutoTimetableController extends Controller
{
    public function __construct(
        protected AutoTimetableGeneratorService $generator,
        protected MyClassRepo $myClass
    ) {
    }

    public function index()
    {
        $classes = MyClass::with(['section' => fn ($q) => $q->where('active', 1)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $sections = $classes->flatMap(fn (MyClass $c) => $c->section)->values();
        $savedSectionIds = $this->generator->savedSectionIds();

        return view('pages.super_admin.auto_timetable.index', compact('classes', 'sections', 'savedSectionIds'));
    }

    public function loadSaved(Request $req)
    {
        $req->validate([
            'section_ids'   => 'required|array|min:1',
            'section_ids.*' => 'integer|exists:sections,id',
        ]);

        $result = $this->generator->loadSavedForSections($req->section_ids);

        return response()->json($result);
    }

    public function sectionSubjects(int $sectionId)
    {
        return response()->json([
            'ok'           => true,
            'subjects'     => $this->generator->subjectsForSection($sectionId),
            'default_plan' => $this->generator->defaultPlanForSection($sectionId),
        ]);
    }

    public function buildSlots(Request $req)
    {
        $req->validate([
            'school_start'  => 'required|string',
            'school_end'    => 'required|string',
            'period_count'  => 'required|integer|min:1|max:12',
            'breaks'        => 'nullable|array',
        ]);

        $slots = $this->generator->buildDefaultSlots(
            $req->school_start,
            $req->school_end,
            (int) $req->period_count,
            $req->breaks ?? []
        );

        return response()->json(['ok' => true, 'slots' => $slots]);
    }

    public function preview(Request $req)
    {
        $config = $this->validatedConfig($req);
        $result = $this->generator->run($config, false);

        return response()->json($result);
    }

    public function generate(Request $req)
    {
        $config = $this->validatedConfig($req);
        $result = $this->generator->run($config, true);

        return response()->json($result);
    }

    public function savePreview(Request $req)
    {
        $req->validate([
            'name'              => 'nullable|string|max:100',
            'sections'          => 'required|array|min:1',
            'sections.*.section_id' => 'required|integer|exists:sections,id',
            'sections.*.days'   => 'required|array|min:1',
            'sections.*.slots'  => 'required|array|min:1',
            'sections.*.grid'   => 'required|array',
        ]);

        $result = $this->generator->persistPreview([
            'name'     => $req->name ?? 'Auto Timetable',
            'year'     => Qs::getCurrentSession(),
            'sections' => $req->sections,
        ]);

        return response()->json($result);
    }

    public function swapCells(Request $req)
    {
        $req->validate([
            'ttr_id'     => 'required|integer|exists:time_table_records,id',
            'day_a'      => 'required|string',
            'slot_a'     => 'required|integer|min:0',
            'day_b'      => 'required|string',
            'slot_b'     => 'required|integer|min:0',
        ]);

        $result = $this->generator->swapCells(
            (int) $req->ttr_id,
            $req->day_a,
            (int) $req->slot_a,
            $req->day_b,
            (int) $req->slot_b
        );

        $status = $result['ok'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function updateCell(Request $req)
    {
        $req->validate([
            'ttr_id'       => 'required|integer|exists:time_table_records,id',
            'day'          => 'required|string',
            'slot_index'   => 'required|integer|min:0',
            'subject_id'   => 'nullable|integer|exists:subjects,id',
            'teacher_id'   => 'nullable|integer|exists:users,id',
            'slots'        => 'required|array',
        ]);

        $ttrId = (int) $req->ttr_id;
        $day = $req->day;
        $slotIndex = (int) $req->slot_index;
        $slots = $req->slots;

        $timeSlots = \App\Models\TimeSlot::where('ttr_id', $ttrId)->orderBy('sort_order')->get();
        $ts = $timeSlots->values()->get($slotIndex);
        if (!$ts || $ts->slot_type === 'break') {
            return response()->json(['ok' => false, 'message' => 'Cannot assign a subject to a break period.'], 422);
        }

        $existing = TimeTable::where('ttr_id', $ttrId)->where('day', $day)->where('ts_id', $ts->id)->first();

        if (!$req->subject_id || !$req->teacher_id) {
            $existing?->delete();
            return response()->json(['ok' => true, 'message' => 'Cell cleared.']);
        }

        $subject = \App\Models\Subject::with('department')->find($req->subject_id);
        $teachers = $this->generator->teachersForSubject((int) $req->subject_id);
        if (!$teachers->contains('id', (int) $req->teacher_id)) {
            return response()->json(['ok' => false, 'message' => 'Selected teacher is not in this subject\'s department.'], 422);
        }

        $conflict = $this->generator->checkCellConflict(
            (int) $req->teacher_id,
            $day,
            $ts->time_from,
            $ts->time_to,
            $ttrId
        );

        if ($conflict) {
            return response()->json(['ok' => false, 'message' => $conflict], 422);
        }

        $data = [
            'ttr_id'         => $ttrId,
            'ts_id'          => $ts->id,
            'subject_id'     => $req->subject_id,
            'teacher_id'     => $req->teacher_id,
            'day'            => $day,
            'timestamp_from' => strtotime($day . ' ' . $ts->time_from),
            'timestamp_to'   => strtotime($day . ' ' . $ts->time_to),
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            TimeTable::create($data);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Cell updated.',
            'cell'    => [
                'subject_id'   => (int) $req->subject_id,
                'teacher_id'   => (int) $req->teacher_id,
                'subject_name' => $subject->name,
                'teacher_name' => $teachers->firstWhere('id', (int) $req->teacher_id)->name,
            ],
        ]);
    }

    protected function validatedConfig(Request $req): array
    {
        $req->validate([
            'name'          => 'nullable|string|max:100',
            'section_ids'   => 'required|array|min:1',
            'section_ids.*' => 'integer|exists:sections,id',
            'days'          => 'required|array|min:1',
            'slots'         => 'required|array|min:1',
            'plans'         => 'required|array',
        ]);

        return [
            'name'         => $req->name ?? 'Auto Timetable',
            'section_ids'  => $req->section_ids,
            'days'         => $req->days,
            'slots'        => $req->slots,
            'plans'        => $req->plans,
            'year'         => Qs::getCurrentSession(),
        ];
    }
}
