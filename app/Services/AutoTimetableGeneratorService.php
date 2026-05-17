<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\Section;
use App\Models\StaffRecord;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\TimeTable;
use App\Models\TimeTableRecord;
use App\Repositories\TimeTableRepo;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AutoTimetableGeneratorService
{
    protected TimeTableRepo $tt;

    public function __construct(TimeTableRepo $tt)
    {
        $this->tt = $tt;
    }

    /**
     * @param  array  $config  sections, days, slots, plans, persist
     * @return array{ok:bool,placement_rate:float,message:string,sections:array}
     */
    public function run(array $config, bool $persist = false): array
    {
        $sectionIds = $config['section_ids'] ?? [];
        $days = $config['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $slotTemplate = $this->normalizeSlotTemplate($config['slots'] ?? []);
        $plans = $config['plans'] ?? [];
        $year = $config['year'] ?? Qs::getCurrentSession();
        $planName = $config['name'] ?? 'Auto Timetable';

        $sections = Section::with('my_class')->whereIn('id', $sectionIds)->where('active', 1)->get();
        if ($sections->isEmpty()) {
            return ['ok' => false, 'placement_rate' => 0, 'message' => 'No valid sections selected.', 'sections' => []];
        }

        $periodSlots = collect($slotTemplate)->where('type', 'period')->values();
        if ($periodSlots->isEmpty()) {
            return ['ok' => false, 'placement_rate' => 0, 'message' => 'Define at least one teaching period.', 'sections' => []];
        }

        $totalRequired = 0;
        $totalPlaced = 0;
        $previewSections = [];
        $globalTeacherBusy = [];

        DB::beginTransaction();
        try {
            foreach ($sections as $section) {
                $sectionPlan = collect($plans[$section->id] ?? $plans[(string) $section->id] ?? []);
                if ($sectionPlan->isEmpty()) {
                    $sectionPlan = collect($this->defaultPlanForSection($section->id));
                }
                $ttr = null;
                $slotModels = [];

                if ($persist) {
                    $ttr = $this->ensureTimetableRecord($section, $year, $planName);
                    $this->tt->deleteTimeSlots(['ttr_id' => $ttr->id]);
                    TimeTable::where('ttr_id', $ttr->id)->delete();
                    $slotModels = $this->persistSlots($ttr->id, $slotTemplate);
                }

                $grid = [];
                foreach ($days as $day) {
                    $grid[$day] = array_fill(0, count($slotTemplate), null);
                }

                $requests = [];
                foreach ($sectionPlan as $row) {
                    $times = max(1, (int) ($row['times_per_week'] ?? 1));
                    $totalRequired += $times;
                    $requests[] = [
                        'subject_id'  => (int) $row['subject_id'],
                        'teacher_id'  => (int) $row['teacher_id'],
                        'times'       => $times,
                        'duration'    => ($row['duration'] ?? 'single') === 'double' ? 'double' : 'single',
                        'subject'     => Subject::with('department')->find($row['subject_id']),
                        'teacher'     => User::find($row['teacher_id']),
                    ];
                }

                usort($requests, fn ($a, $b) => $b['times'] <=> $a['times']);

                foreach ($requests as $req) {
                    if (!$req['subject'] || !$req['teacher']) {
                        continue;
                    }

                    if (!$this->teacherBelongsToSubjectDepartment($req['teacher_id'], $req['subject'])) {
                        continue;
                    }

                    for ($n = 0; $n < $req['times']; $n++) {
                        $placed = $this->placeLesson(
                            $grid,
                            $days,
                            $slotTemplate,
                            $req,
                            $globalTeacherBusy,
                            $section->id
                        );

                        if ($placed) {
                            $totalPlaced++;
                            if ($persist && $ttr) {
                                $this->persistEntry($ttr, $slotModels, $placed, $req);
                            }
                        }
                    }
                }

                $previewSections[] = [
                    'section_id' => $section->id,
                    'ttr_id'       => $ttr?->id,
                    'label'        => $section->my_class->name . ' - ' . $section->name,
                    'class_name'   => $section->my_class->name,
                    'section_name' => $section->name,
                    'days'         => $days,
                    'slots'        => $slotTemplate,
                    'grid'         => $grid,
                ];
            }

            if ($persist) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $rate = $totalRequired > 0 ? round(($totalPlaced / $totalRequired) * 100, 1) : 100.0;

        return [
            'ok'              => $totalPlaced > 0 || $totalRequired === 0,
            'placement_rate'  => $rate,
            'message'         => $persist
                ? "Timetable saved. {$totalPlaced}/{$totalRequired} sessions placed ({$rate}%)."
                : "Preview ready. {$totalPlaced}/{$totalRequired} sessions placed ({$rate}%).",
            'sections'        => $previewSections,
            'placed'          => $totalPlaced,
            'required'        => $totalRequired,
            'saved'           => $persist,
        ];
    }

    /**
     * Load persisted timetables for sections (current session year).
     *
     * @return array{ok:bool,saved:bool,placement_rate:float,message:string,sections:array,plan_name?:string}
     */
    public function loadSavedForSections(array $sectionIds, ?string $year = null): array
    {
        $year = $year ?? Qs::getCurrentSession();
        $previewSections = [];
        $totalPlaced = 0;
        $planName = null;

        $sections = Section::with('my_class')
            ->whereIn('id', $sectionIds)
            ->where('active', 1)
            ->orderBy('my_class_id')
            ->orderBy('name')
            ->get();

        foreach ($sections as $section) {
            $ttr = TimeTableRecord::where('my_class_id', $section->my_class_id)
                ->where('section_id', $section->id)
                ->whereNull('exam_id')
                ->where('year', $year)
                ->first();

            if (!$ttr) {
                continue;
            }

            $timeSlots = TimeSlot::where('ttr_id', $ttr->id)->orderBy('sort_order')->get();
            if ($timeSlots->isEmpty()) {
                continue;
            }

            $entries = TimeTable::with(['subject', 'teacher'])
                ->where('ttr_id', $ttr->id)
                ->get();

            $slots = $timeSlots->map(function (TimeSlot $ts, int $idx) {
                return [
                    'type'       => $ts->slot_type ?: 'period',
                    'label'      => $ts->label ?: ('Period ' . ($idx + 1)),
                    'time_from'  => $ts->time_from,
                    'time_to'    => $ts->time_to,
                    'sort_order' => (int) ($ts->sort_order ?? $idx),
                ];
            })->values()->all();

            $slotCount = count($slots);
            $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $days = $entries->pluck('day')->unique()->filter()->values()->all();
            usort($days, fn ($a, $b) => (array_search($a, $dayOrder, true) ?: 99) <=> (array_search($b, $dayOrder, true) ?: 99));
            if (empty($days)) {
                $days = array_slice($dayOrder, 0, 5);
            }

            $grid = [];
            foreach ($days as $day) {
                $grid[$day] = array_fill(0, $slotCount, null);
            }

            $tsIndex = [];
            foreach ($timeSlots->values() as $idx => $ts) {
                $tsIndex[$ts->id] = $idx;
            }

            foreach ($entries as $entry) {
                $si = $tsIndex[$entry->ts_id] ?? null;
                if ($si === null || !$entry->day) {
                    continue;
                }
                if (!isset($grid[$entry->day])) {
                    $grid[$entry->day] = array_fill(0, $slotCount, null);
                    $days[] = $entry->day;
                }
                $grid[$entry->day][$si] = [
                    'subject_id'   => (int) $entry->subject_id,
                    'teacher_id'   => (int) $entry->teacher_id,
                    'subject_name' => $entry->subject?->name ?? '',
                    'teacher_name' => $entry->teacher?->name ?? '',
                    'color'        => $this->subjectColor((int) $entry->subject_id),
                ];
                $totalPlaced++;
            }

            if (!$planName && $ttr->name) {
                $planName = preg_replace('/\s*-\s*[^-]+\s*-\s*[^-]+$/', '', $ttr->name) ?: $ttr->name;
            }

            $previewSections[] = [
                'section_id'   => $section->id,
                'ttr_id'       => $ttr->id,
                'label'        => $section->my_class->name . ' - ' . $section->name,
                'class_name'   => $section->my_class->name,
                'section_name' => $section->name,
                'days'         => array_values(array_unique($days)),
                'slots'        => $slots,
                'grid'         => $grid,
            ];
        }

        if (empty($previewSections)) {
            return [
                'ok'             => false,
                'saved'          => false,
                'placement_rate' => 0,
                'message'        => 'No saved timetable found for the selected sections.',
                'sections'       => [],
            ];
        }

        return [
            'ok'             => true,
            'saved'          => true,
            'placement_rate' => 100.0,
            'message'        => 'Loaded saved timetable (' . count($previewSections) . ' section(s)).',
            'sections'       => $previewSections,
            'placed'         => $totalPlaced,
            'plan_name'      => $planName,
        ];
    }

    /**
     * Section IDs that already have a saved auto timetable for the current session.
     *
     * @return array<int>
     */
    public function savedSectionIds(?string $year = null): array
    {
        $year = $year ?? Qs::getCurrentSession();

        return TimeTableRecord::query()
            ->where('year', $year)
            ->whereNull('exam_id')
            ->whereNotNull('section_id')
            ->pluck('section_id')
            ->unique()
            ->values()
            ->all();
    }

    public function teachersForSubject(int $subjectId): Collection
    {
        $subject = Subject::with('department')->find($subjectId);
        if (!$subject || !$subject->department_id) {
            return collect();
        }

        return User::where('user_type', 'teacher')
            ->whereHas('staff', fn ($q) => $q->where('department_id', $subject->department_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function subjectsForSection(int $sectionId): Collection
    {
        $section = Section::find($sectionId);
        if (!$section) {
            return collect();
        }

        return Subject::with('department')
            ->where('my_class_id', $section->my_class_id)
            ->orderBy('name')
            ->get()
            ->map(function (Subject $s) {
                $teachers = $this->teachersForSubject($s->id);
                $defaultTeacherId = $teachers->first()?->id;

                return [
                    'id'                  => $s->id,
                    'name'                => $s->name,
                    'department'          => $s->department?->name,
                    'department_id'       => $s->department_id,
                    'default_teacher_id'  => $defaultTeacherId,
                    'teachers'            => $teachers->map(fn ($t) => [
                        'id'   => $t->id,
                        'name' => $t->name,
                    ])->values(),
                ];
            });
    }

    /**
     * Build timetable plan rows from class subjects and department-assigned teachers.
     *
     * @return array<int, array{subject_id:int,teacher_id:int,times_per_week:int}>
     */
    public function defaultPlanForSection(int $sectionId, int $timesPerWeek = 5): array
    {
        return $this->subjectsForSection($sectionId)
            ->filter(fn ($s) => !empty($s['department_id']) && !empty($s['default_teacher_id']))
            ->map(fn ($s) => [
                'subject_id'     => (int) $s['id'],
                'teacher_id'     => (int) $s['default_teacher_id'],
                'times_per_week' => $timesPerWeek,
                'duration'       => 'single',
            ])
            ->values()
            ->all();
    }

    /**
     * Build default period slots between school start/end.
     */
    public function buildDefaultSlots(string $start, string $end, int $periodCount, array $breaks = []): array
    {
        $startAt = Carbon::parse($start);
        $endAt = Carbon::parse($end);
        $totalMinutes = $startAt->diffInMinutes($endAt);

        $breakMinutes = 0;
        foreach ($breaks as $b) {
            $breakMinutes += Carbon::parse($b['from'])->diffInMinutes(Carbon::parse($b['to']));
        }

        $periodMinutes = max(15, (int) floor(($totalMinutes - $breakMinutes) / max(1, $periodCount)));
        $slots = [];
        $cursor = $startAt->copy();
        $order = 0;
        $periodsMade = 0;

        while ($periodsMade < $periodCount && $cursor->lt($endAt)) {
            foreach ($breaks as $break) {
                $bStart = Carbon::parse($break['from']);
                $bEnd = Carbon::parse($break['to']);
                if ($cursor->between($bStart, $bEnd->copy()->subMinute()) || $cursor->equalTo($bStart)) {
                    $slots[] = [
                        'type'       => 'break',
                        'label'      => $break['label'] ?? 'Break',
                        'time_from'  => $bStart->format('g:i A'),
                        'time_to'    => $bEnd->format('g:i A'),
                        'sort_order' => $order++,
                    ];
                    $cursor = $bEnd->copy();
                }
            }

            $periodEnd = $cursor->copy()->addMinutes($periodMinutes);
            if ($periodEnd->gt($endAt)) {
                $periodEnd = $endAt->copy();
            }

            $slots[] = [
                'type'       => 'period',
                'label'      => 'Period ' . ($periodsMade + 1),
                'time_from'  => $cursor->format('g:i A'),
                'time_to'    => $periodEnd->format('g:i A'),
                'sort_order' => $order++,
            ];
            $cursor = $periodEnd->copy();
            $periodsMade++;
        }

        return $slots;
    }

    public function checkCellConflict(int $teacherId, string $day, string $timeFrom, string $timeTo, ?int $excludeTtrId = null): ?string
    {
        $year = Qs::getCurrentSession();

        $conflict = TimeTable::query()
            ->where('day', $day)
            ->where('teacher_id', $teacherId)
            ->when($excludeTtrId, fn ($q) => $q->where('ttr_id', '!=', $excludeTtrId))
            ->whereHas('time_slot', fn ($q) => $q->where('time_from', $timeFrom)->where('time_to', $timeTo))
            ->whereHas('tt_record', fn ($q) => $q->where('year', $year))
            ->with(['tt_record.my_class', 'tt_record.section'])
            ->first();

        if ($conflict) {
            $label = $conflict->tt_record->section
                ? $conflict->tt_record->my_class->name . ' - ' . $conflict->tt_record->section->name
                : ($conflict->tt_record->my_class->name ?? 'another section');
            return "Teacher is already scheduled for {$label} at this time on {$day}.";
        }

        return null;
    }

    protected function placeLesson(array &$grid, array $days, array $slotTemplate, array $req, array &$globalTeacherBusy, int $sectionId): ?array
    {
        $periodIndexes = [];
        foreach ($slotTemplate as $idx => $slot) {
            if (($slot['type'] ?? 'period') === 'period') {
                $periodIndexes[] = $idx;
            }
        }

        $span = ($req['duration'] ?? 'single') === 'double' ? 2 : 1;
        $candidates = [];
        foreach ($days as $day) {
            $subjectCountToday = 0;
            foreach ($periodIndexes as $pi) {
                $cell = $grid[$day][$pi] ?? null;
                if ($cell && (int) $cell['subject_id'] === (int) $req['subject_id']) {
                    $subjectCountToday++;
                }
            }
            if ($subjectCountToday >= 2) {
                continue;
            }

            for ($i = 0; $i <= count($periodIndexes) - $span; $i++) {
                $slotIndexes = array_slice($periodIndexes, $i, $span);
                if (count($slotIndexes) < $span) {
                    continue;
                }

                $blocked = false;
                foreach ($slotIndexes as $pi) {
                    if (!empty($grid[$day][$pi])) {
                        $blocked = true;
                        break;
                    }
                    $busyKey = "{$day}|{$pi}";
                    if (isset($globalTeacherBusy[$busyKey]) && (int) $globalTeacherBusy[$busyKey] === (int) $req['teacher_id']) {
                        $blocked = true;
                        break;
                    }
                }
                if ($blocked) {
                    continue;
                }

                $candidates[] = [
                    'day'           => $day,
                    'slot_index'    => $slotIndexes[0],
                    'slot_indexes'  => $slotIndexes,
                ];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        shuffle($candidates);
        $pick = $candidates[0];
        foreach ($pick['slot_indexes'] as $pi) {
            $globalTeacherBusy["{$pick['day']}|{$pi}"] = $req['teacher_id'];
        }

        $entry = [
            'subject_id'    => $req['subject_id'],
            'teacher_id'    => $req['teacher_id'],
            'subject_name'  => $req['subject']->name,
            'teacher_name'  => $req['teacher']->name,
            'day'           => $pick['day'],
            'slot_index'    => $pick['slot_index'],
            'slot_indexes'  => $pick['slot_indexes'],
            'color'         => $this->subjectColor($req['subject_id']),
        ];

        foreach ($pick['slot_indexes'] as $pi) {
            $grid[$pick['day']][$pi] = $entry;
        }

        return $entry;
    }

    protected function persistEntry(TimeTableRecord $ttr, array $slotModels, array $placed, array $req): void
    {
        $indexes = $placed['slot_indexes'] ?? [$placed['slot_index']];
        $dDate = $placed['day'];

        foreach ($indexes as $si) {
            $ts = $slotModels[$si] ?? null;
            if (!$ts || $ts->slot_type === 'break') {
                continue;
            }

            TimeTable::create([
                'ttr_id'          => $ttr->id,
                'ts_id'           => $ts->id,
                'subject_id'      => $req['subject_id'],
                'teacher_id'      => $req['teacher_id'],
                'day'             => $dDate,
                'timestamp_from'  => strtotime($dDate . ' ' . $ts->time_from),
                'timestamp_to'    => strtotime($dDate . ' ' . $ts->time_to),
            ]);
        }
    }

    protected function persistSlots(int $ttrId, array $slotTemplate): array
    {
        $models = [];
        foreach ($slotTemplate as $idx => $slot) {
            $from = $slot['time_from'];
            $to = $slot['time_to'];
            $parsedFrom = Carbon::parse($from);
            $parsedTo = Carbon::parse($to);

            $models[$idx] = TimeSlot::create([
                'ttr_id'          => $ttrId,
                'slot_type'       => $slot['type'] ?? 'period',
                'sort_order'      => $slot['sort_order'] ?? $idx,
                'label'           => $slot['label'] ?? null,
                'hour_from'       => (int) $parsedFrom->format('g'),
                'min_from'        => $parsedFrom->format('i'),
                'meridian_from'   => $parsedFrom->format('A'),
                'hour_to'         => (int) $parsedTo->format('g'),
                'min_to'          => $parsedTo->format('i'),
                'meridian_to'     => $parsedTo->format('A'),
                'time_from'       => $from,
                'time_to'         => $to,
                'timestamp_from'  => (string) $parsedFrom->timestamp,
                'timestamp_to'    => (string) $parsedTo->timestamp,
                'full'            => $from . ' - ' . $to,
            ]);
        }

        return $models;
    }

    protected function ensureTimetableRecord(Section $section, string $year, string $planName): TimeTableRecord
    {
        $name = $planName . ' - ' . $section->my_class->name . ' - ' . $section->name;

        return TimeTableRecord::updateOrCreate(
            [
                'my_class_id' => $section->my_class_id,
                'section_id'  => $section->id,
                'exam_id'     => null,
                'year'        => $year,
            ],
            ['name' => $name]
        );
    }

    protected function normalizeSlotTemplate(array $slots): array
    {
        return collect($slots)->map(function ($slot, $idx) {
            return [
                'type'       => $slot['type'] ?? 'period',
                'label'      => $slot['label'] ?? ('Period ' . ($idx + 1)),
                'time_from'  => $slot['time_from'],
                'time_to'    => $slot['time_to'],
                'sort_order' => $slot['sort_order'] ?? $idx,
            ];
        })->values()->all();
    }

    protected function teacherBelongsToSubjectDepartment(int $teacherId, Subject $subject): bool
    {
        if (!$subject->department_id) {
            return false;
        }

        return StaffRecord::where('user_id', $teacherId)
            ->where('department_id', $subject->department_id)
            ->exists();
    }

    protected function subjectColor(int $subjectId): string
    {
        $palette = ['#e8eaf6', '#e3f2fd', '#e8f5e9', '#fff3e0', '#fce4ec', '#f3e5f5', '#e0f7fa', '#f9fbe7'];
        return $palette[$subjectId % count($palette)];
    }

    /**
     * Save timetable grids from the preview editor (preserves manual edits).
     *
     * @param  array{sections:array,name?:string,year?:string}  $config
     */
    public function persistPreview(array $config): array
    {
        $year = $config['year'] ?? Qs::getCurrentSession();
        $planName = $config['name'] ?? 'Auto Timetable';
        $previewSections = [];

        DB::beginTransaction();
        try {
            foreach ($config['sections'] ?? [] as $payload) {
                $section = Section::with('my_class')->find($payload['section_id'] ?? 0);
                if (!$section) {
                    continue;
                }

                $slots = $payload['slots'] ?? [];
                $days = $payload['days'] ?? [];
                $grid = $payload['grid'] ?? [];

                $ttr = $this->ensureTimetableRecord($section, $year, $planName);
                $this->tt->deleteTimeSlots(['ttr_id' => $ttr->id]);
                TimeTable::where('ttr_id', $ttr->id)->delete();
                $slotModels = $this->persistSlots($ttr->id, $slots);

                $written = [];
                foreach ($days as $day) {
                    foreach ($grid[$day] ?? [] as $si => $cell) {
                        if (!$cell || !is_array($cell)) {
                            continue;
                        }
                        $slot = $slots[$si] ?? null;
                        if (!$slot || ($slot['type'] ?? 'period') === 'break') {
                            continue;
                        }
                        $key = "{$day}|{$si}";
                        if (isset($written[$key])) {
                            continue;
                        }
                        $written[$key] = true;

                        $ts = $slotModels[$si] ?? null;
                        if (!$ts) {
                            continue;
                        }

                        TimeTable::create([
                            'ttr_id'          => $ttr->id,
                            'ts_id'           => $ts->id,
                            'subject_id'      => (int) $cell['subject_id'],
                            'teacher_id'      => (int) $cell['teacher_id'],
                            'day'             => $day,
                            'timestamp_from'  => strtotime($day . ' ' . $ts->time_from),
                            'timestamp_to'    => strtotime($day . ' ' . $ts->time_to),
                        ]);
                    }
                }

                $previewSections[] = [
                    'section_id'   => $section->id,
                    'ttr_id'         => $ttr->id,
                    'label'          => $section->my_class->name . ' - ' . $section->name,
                    'class_name'     => $section->my_class->name,
                    'section_name'   => $section->name,
                    'days'           => $days,
                    'slots'          => $slots,
                    'grid'           => $grid,
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'ok'       => true,
            'saved'    => true,
            'message'  => 'Timetable saved with your customizations.',
            'sections' => $previewSections,
        ];
    }

    /**
     * Swap two period cells on a saved timetable.
     */
    public function swapCells(int $ttrId, string $dayA, int $slotA, string $dayB, int $slotB): array
    {
        $timeSlots = TimeSlot::where('ttr_id', $ttrId)->orderBy('sort_order')->get();
        $tsA = $timeSlots->values()->get($slotA);
        $tsB = $timeSlots->values()->get($slotB);

        if (!$tsA || !$tsB || $tsA->slot_type === 'break' || $tsB->slot_type === 'break') {
            return ['ok' => false, 'message' => 'Cannot swap break periods.'];
        }

        $entryA = TimeTable::where('ttr_id', $ttrId)->where('day', $dayA)->where('ts_id', $tsA->id)->first();
        $entryB = TimeTable::where('ttr_id', $ttrId)->where('day', $dayB)->where('ts_id', $tsB->id)->first();

        if ($entryA && $entryB) {
            $conflictA = $this->checkCellConflict((int) $entryB->teacher_id, $dayA, $tsA->time_from, $tsA->time_to, $ttrId);
            $conflictB = $this->checkCellConflict((int) $entryA->teacher_id, $dayB, $tsB->time_from, $tsB->time_to, $ttrId);
            if ($conflictA || $conflictB) {
                return ['ok' => false, 'message' => $conflictA ?: $conflictB];
            }
        } elseif ($entryA) {
            $conflict = $this->checkCellConflict((int) $entryA->teacher_id, $dayB, $tsB->time_from, $tsB->time_to, $ttrId);
            if ($conflict) {
                return ['ok' => false, 'message' => $conflict];
            }
        } elseif ($entryB) {
            $conflict = $this->checkCellConflict((int) $entryB->teacher_id, $dayA, $tsA->time_from, $tsA->time_to, $ttrId);
            if ($conflict) {
                return ['ok' => false, 'message' => $conflict];
            }
        }

        $payloadA = $entryA ? [
            'subject_id' => $entryA->subject_id,
            'teacher_id' => $entryA->teacher_id,
        ] : null;
        $payloadB = $entryB ? [
            'subject_id' => $entryB->subject_id,
            'teacher_id' => $entryB->teacher_id,
        ] : null;

        $entryA?->delete();
        $entryB?->delete();

        $cellA = null;
        $cellB = null;

        if ($payloadB) {
            $subject = Subject::find($payloadB['subject_id']);
            $teacher = User::find($payloadB['teacher_id']);
            TimeTable::create([
                'ttr_id'         => $ttrId,
                'ts_id'          => $tsA->id,
                'subject_id'     => $payloadB['subject_id'],
                'teacher_id'     => $payloadB['teacher_id'],
                'day'            => $dayA,
                'timestamp_from' => strtotime($dayA . ' ' . $tsA->time_from),
                'timestamp_to'   => strtotime($dayA . ' ' . $tsA->time_to),
            ]);
            $cellA = [
                'subject_id'   => (int) $payloadB['subject_id'],
                'teacher_id'   => (int) $payloadB['teacher_id'],
                'subject_name' => $subject?->name ?? '',
                'teacher_name' => $teacher?->name ?? '',
                'color'        => $this->subjectColor((int) $payloadB['subject_id']),
            ];
        }

        if ($payloadA) {
            $subject = Subject::find($payloadA['subject_id']);
            $teacher = User::find($payloadA['teacher_id']);
            TimeTable::create([
                'ttr_id'         => $ttrId,
                'ts_id'          => $tsB->id,
                'subject_id'     => $payloadA['subject_id'],
                'teacher_id'     => $payloadA['teacher_id'],
                'day'            => $dayB,
                'timestamp_from' => strtotime($dayB . ' ' . $tsB->time_from),
                'timestamp_to'   => strtotime($dayB . ' ' . $tsB->time_to),
            ]);
            $cellB = [
                'subject_id'   => (int) $payloadA['subject_id'],
                'teacher_id'   => (int) $payloadA['teacher_id'],
                'subject_name' => $subject?->name ?? '',
                'teacher_name' => $teacher?->name ?? '',
                'color'        => $this->subjectColor((int) $payloadA['subject_id']),
            ];
        }

        return [
            'ok'     => true,
            'message'=> 'Periods exchanged.',
            'cell_a' => $cellA,
            'cell_b' => $cellB,
            'day_a'  => $dayA,
            'day_b'  => $dayB,
            'slot_a' => $slotA,
            'slot_b' => $slotB,
        ];
    }
}
