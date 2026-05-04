<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CalendarConflict;
use App\Models\CalendarEvent;
use App\Models\CalendarRule;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AcademicCalendarGeneratorService
{
    protected AcademicYear $year;
    protected array $holidayDates = [];   // ['Y-m-d' => 'Holiday Name']
    protected array $generatedEvents = [];
    protected array $conflicts = [];
    protected ?string $sem2Start = null;

    // ── Entry point ───────────────────────────────────────────────────────────

    /**
     * Full pipeline: rules → holidays → events → conflicts → publish.
     */
    public function generateAcademicYear(int $startYear): AcademicYear
    {
        DB::beginTransaction();
        try {
            $this->year = $this->createAcademicYearRecord($startYear);

            $this->importHolidays('ET', $startYear);
            $this->importHolidays('ET', $startYear + 1); // year spans two Gregorian years

            $this->generateEventsFromRules();
            $this->resolveConflicts();
            $this->publishCalendar();

            DB::commit();
            Log::info("Academic year {$this->year->name} generated successfully.");
            return $this->year;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Academic year generation failed: '.$e->getMessage());
            throw $e;
        }
    }

    // ── Step 1: Create academic year record ───────────────────────────────────

    protected function createAcademicYearRecord(int $startYear): AcademicYear
    {
        AcademicYear::where('is_current', true)
            ->update(['is_current' => false, 'status' => 'archived']);
        AcademicYear::where('status', 'active')
            ->update(['status' => 'archived']);

        $endYear = $startYear + 1;

        // Ethiopian year: before Sep 11 the ET year is GC - 8, from Sep 11 onward it's GC - 7.
        // The academic year STARTS on Sep 11, so the ET start year = startYear - 7.
        // e.g. GC 2026 Sep 11 = ET 2019 Meskerem 1  →  eth_name = "2019/20 E.C."
        $etStart = $startYear - 7;   // ET year that begins on Sep 11 of startYear
        $etEnd   = $etStart + 1;

        return AcademicYear::create([
            'name'         => $startYear . '/' . $endYear,
            'eth_name'     => $etStart . '/' . ($etEnd - 2000) . ' E.C.',
            'start_date'   => Carbon::create($startYear, 9, 11),
            'end_date'     => Carbon::create($endYear, 7, 7),
            'status'       => 'draft',
            'is_current'   => true,
            'generated_by' => auth()->id(),
        ]);
    }

    // ── Step 2: Import holidays ───────────────────────────────────────────────

    /**
     * Import Ethiopian public holidays from Nager.Date API (free, no key needed).
     * Falls back to built-in rule-based holidays if API is unavailable.
     */
    public function importHolidays(string $country, int $year): void
    {
        try {
            $response = Http::timeout(8)
                ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country}");

            if ($response->successful()) {
                foreach ($response->json() as $h) {
                    $date = $h['date'];
                    $this->holidayDates[$date] = $h['localName'] ?? $h['name'];

                    Holiday::updateOrCreate(
                        ['academic_year_id' => $this->year->id, 'date' => $date],
                        [
                            'name'         => $h['name'],
                            'source'       => 'api',
                            'country_code' => $country,
                            'is_school_day'=> false,
                        ]
                    );
                }
                Log::info("Imported ".count($response->json())." holidays for {$country}/{$year}");
                return;
            }
        } catch (\Exception $e) {
            Log::warning("Holiday API unavailable: ".$e->getMessage().". Using built-in rules.");
        }

        // Fallback: generate from built-in rule list
        $this->generateHolidaysFromRules($year);
    }

    protected function generateHolidaysFromRules(int $year): void
    {
        $rules = CalendarRule::active()->where('event_type', 'holiday')->get();
        foreach ($rules as $rule) {
            $date = $this->resolveRuleDate($rule, $year);
            if (!$date) continue;

            $dateStr = $date->format('Y-m-d');
            $this->holidayDates[$dateStr] = $rule->name;

            Holiday::updateOrCreate(
                ['academic_year_id' => $this->year->id, 'date' => $dateStr],
                ['name' => $rule->name, 'source' => 'generated', 'country_code' => 'ET', 'is_school_day' => false]
            );
        }
    }

    // ── Step 3: Generate events from rules ────────────────────────────────────

    protected function generateEventsFromRules(): void
    {
        $rules     = CalendarRule::active()->get();
        $yearStart = $this->year->start_date->year;

        foreach ($rules as $rule) {
            // Determine which Gregorian year to use for this rule
            $ruleYear = $this->pickYearForRule($rule, $yearStart);
            $date     = $this->resolveRuleDate($rule, $ruleYear);
            if (!$date) continue;

            // Store sem2 start for offset calculations
            if ($rule->name === 'Semester 2 Start') {
                $this->sem2Start = $date->format('Y-m-d');
            }

            $endDate = $this->resolveEndDate($rule, $date);

            $event = CalendarEvent::create([
                'academic_year_id' => $this->year->id,
                'calendar_rule_id' => $rule->id,
                'title'            => $rule->name,
                'description'      => $rule->description,
                'start_date'       => $date->format('Y-m-d'),
                'end_date'         => $endDate,
                'color'            => $rule->color,
                'type'             => $rule->event_type,
                'all_day'          => true,
                'notify_email'     => $rule->notify_email,
                'notify_roles'     => $rule->notify_roles,
                'auto_generated'   => true,
                'created_by'       => auth()->id(),
            ]);

            $this->generatedEvents[] = $event;
        }
    }

    // ── Step 4: Resolve conflicts ─────────────────────────────────────────────

    public function resolveConflicts(): array
    {
        $this->conflicts = [];

        foreach ($this->generatedEvents as $event) {
            $original = Carbon::parse($event->start_date);
            $resolved = clone $original;
            $reason   = null;

            // Check: weekend
            if ($resolved->isWeekend() && $event->type === 'exam') {
                $reason = 'weekend';
                $resolved = $this->nextSchoolDay($resolved);
            }

            // Check: holiday overlap
            if (isset($this->holidayDates[$resolved->format('Y-m-d')]) && $event->type === 'exam') {
                $reason = 'holiday_overlap';
                $resolved = $this->nextSchoolDay($resolved->addDay());
            }

            if ($reason) {
                $aiSuggestion = $this->askAiForSuggestion($event, $original, $resolved, $reason);

                $conflict = CalendarConflict::create([
                    'academic_year_id'  => $this->year->id,
                    'calendar_event_id' => $event->id,
                    'conflict_type'     => $reason,
                    'original_date'     => $original->format('Y-m-d'),
                    'resolved_date'     => $resolved->format('Y-m-d'),
                    'resolution'        => "Moved to next valid school day: ".$resolved->format('D, d M Y'),
                    'ai_suggestion'     => $aiSuggestion,
                ]);

                $event->update([
                    'start_date'        => $resolved->format('Y-m-d'),
                    'conflict_resolved' => true,
                    'conflict_note'     => $conflict->resolution,
                ]);

                $this->conflicts[] = $conflict;
            }
        }

        return $this->conflicts;
    }

    // ── Step 5: Publish ───────────────────────────────────────────────────────

    public function publishCalendar(): AcademicYear
    {
        $this->year->update([
            'status'       => 'active',
            'published_at' => now(),
        ]);
        return $this->year;
    }

    // ── Rule date resolver ────────────────────────────────────────────────────

    public function resolveRuleDate(CalendarRule $rule, int $year): ?Carbon
    {
        $v = $rule->rule_value;

        return match ($rule->rule_type) {

            // e.g. {'month': 9, 'day': 11}
            'fixed_month_day' => Carbon::create($year, $v['month'], $v['day']),

            // e.g. {'weeks': 8, 'duration_days': 5} — offset from academic year start
            'week_offset_from_start' => Carbon::parse($this->year->start_date)
                ->addWeeks($v['weeks']),

            // e.g. offset from semester 2 start
            'week_offset_from_sem2' => $this->sem2Start
                ? Carbon::parse($this->sem2Start)->addWeeks($v['weeks'])
                : null,

            // e.g. {'n': 1, 'weekday': 1, 'month': 9} — 1st Monday of September
            'nth_weekday' => $this->nthWeekdayOfMonth($year, $v['month'], $v['n'], $v['weekday']),

            // Orthodox Easter calculation
            'easter_offset' => $this->orthodoxEaster($year)->addDays($v['offset_days']),

            // Islamic holidays — approximate via Hijri offset
            'islamic_holiday' => $this->islamicHolidayDate($v['holiday'], $year),

            default => null,
        };
    }

    // ── Date helpers ──────────────────────────────────────────────────────────

    protected function resolveEndDate(CalendarRule $rule, Carbon $start): ?string
    {
        $v = $rule->rule_value;
        if (isset($v['duration_days']) && $v['duration_days'] > 1) {
            return $start->copy()->addDays($v['duration_days'] - 1)->format('Y-m-d');
        }
        return null;
    }

    protected function nextSchoolDay(Carbon $date): Carbon
    {
        $d = $date->copy();
        $attempts = 0;
        while ($d->isWeekend() || isset($this->holidayDates[$d->format('Y-m-d')])) {
            $d->addDay();
            if (++$attempts > 14) break; // safety valve
        }
        return $d;
    }

    protected function pickYearForRule(CalendarRule $rule, int $startYear): int
    {
        $v = $rule->rule_value;
        // Rules with month Jan-Jul belong to the second Gregorian year of the academic year
        if (isset($v['month']) && $v['month'] <= 7) {
            return $startYear + 1;
        }
        return $startYear;
    }

    protected function nthWeekdayOfMonth(int $year, int $month, int $n, int $weekday): Carbon
    {
        // weekday: 1=Monday … 7=Sunday (ISO)
        $first = Carbon::create($year, $month, 1);
        $diff  = ($weekday - $first->dayOfWeekIso + 7) % 7;
        $first->addDays($diff + ($n - 1) * 7);
        return $first;
    }

    /**
     * Orthodox Easter (Julian calendar, converted to Gregorian).
     * Uses the Meeus Julian algorithm.
     */
    protected function orthodoxEaster(int $year): Carbon
    {
        $a = $year % 4;
        $b = $year % 7;
        $c = $year % 19;
        $d = (19 * $c + 15) % 30;
        $e = (2 * $a + 4 * $b - $d + 34) % 7;
        $f = intdiv($d + $e + 114, 31);
        $g = ($d + $e + 114) % 31 + 1;
        // Julian date → add 13 days for Gregorian
        return Carbon::createFromDate($year, $f, $g)->addDays(13);
    }

    /**
     * Approximate Islamic holiday dates using a simple Hijri offset table.
     * For production, replace with a proper Hijri library or API.
     */
    protected function islamicHolidayDate(string $holiday, int $year): ?Carbon
    {
        // Approximate Gregorian dates for key Islamic holidays (shift ~11 days/year)
        // Base year 2024 reference dates
        $base = [
            'eid_al_fitr' => Carbon::create(2024, 4, 10),
            'eid_al_adha' => Carbon::create(2024, 6, 16),
            'mawlid'      => Carbon::create(2024, 9, 15),
        ];

        if (!isset($base[$holiday])) return null;

        $yearDiff = $year - 2024;
        // Islamic year is ~354 days; shift ≈ 11 days per Gregorian year
        return $base[$holiday]->copy()->subDays($yearDiff * 11);
    }

    // ── AI edge-case assistant ────────────────────────────────────────────────

    protected function askAiForSuggestion(CalendarEvent $event, Carbon $original, Carbon $resolved, string $reason): ?string
    {
        try {
            $ollamaUrl = rtrim(env('OLLAMA_URL', 'http://127.0.0.1:11434'), '/');
            $model     = env('OLLAMA_MODEL', 'tinyllama');

            $prompt = "You are an Ethiopian school calendar assistant.\n"
                ."A scheduling conflict was detected:\n"
                ."- Event: {$event->title}\n"
                ."- Original date: {$original->format('D, d M Y')}\n"
                ."- Conflict reason: {$reason}\n"
                ."- Auto-resolved to: {$resolved->format('D, d M Y')}\n\n"
                ."In 1-2 sentences, confirm this resolution is appropriate for an Ethiopian school "
                ."and suggest any alternative if the resolved date is also problematic. Be concise.";

            $response = Http::timeout(10)->post("{$ollamaUrl}/api/generate", [
                'model'   => $model,
                'prompt'  => $prompt,
                'stream'  => false,
                'options' => ['temperature' => 0.3, 'num_predict' => 80],
            ]);

            if ($response->successful()) {
                return trim($response->json('response') ?? '');
            }
        } catch (\Exception $e) {
            Log::info('Ollama AI suggestion skipped: '.$e->getMessage());
        }

        return null;
    }

    // ── Public helpers ────────────────────────────────────────────────────────

    public function getGeneratedEvents(): array  { return $this->generatedEvents; }
    public function getConflicts(): array         { return $this->conflicts; }
    public function getHolidayDates(): array      { return $this->holidayDates; }
}
