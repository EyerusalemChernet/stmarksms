<?php

namespace App\Services;

use App\Models\EthiopianHoliday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * EthiopianHolidayService
 *
 * Calculates and manages Ethiopian public holidays.
 *
 * Ethiopian calendar notes:
 *   - Ethiopia uses the Ge'ez calendar (13 months, ~7 years behind Gregorian)
 *   - The Ethiopian year starts on 11 September (12 Sep in leap years)
 *   - Orthodox Christian holidays follow the Julian calendar
 *   - Islamic holidays follow the Hijri calendar (not implemented here —
 *     they shift ~11 days per year and are not official public holidays
 *     in Ethiopia's school calendar)
 *
 * All dates returned are Gregorian (Carbon instances).
 *
 * Sources:
 *   - Ethiopian Federal Government official holiday list
 *   - Ethiopian Orthodox Tewahedo Church calendar
 */
class EthiopianHolidayService
{
    /**
     * Get all Ethiopian public holidays for a Gregorian year as a Collection.
     * Includes holidays that fall in Jan–Dec of that Gregorian year.
     *
     * @param  int $year  Gregorian year (e.g. 2024)
     * @return Collection  of ['date' => Carbon, 'name' => string, 'type' => string]
     */
    public function getHolidaysForYear(int $year): Collection
    {
        $holidays = collect();

        // ── Fixed Gregorian-date holidays ────────────────────────────────────

        // Ethiopian Christmas (Genna) — 7 January (Julian Dec 25 = Gregorian Jan 7)
        $holidays->push($this->make("{$year}-01-07", 'Genna (Ethiopian Christmas)', 'religious'));

        // Epiphany (Timkat) — 19 January (Julian Jan 6 = Gregorian Jan 19)
        $holidays->push($this->make("{$year}-01-19", 'Timkat (Ethiopian Epiphany)', 'religious'));

        // Victory of Adwa — 2 March
        $holidays->push($this->make("{$year}-03-02", 'Victory of Adwa', 'public'));

        // Good Friday (Siklet) — calculated (see below)
        $goodFriday = $this->orthodoxGoodFriday($year);
        if ($goodFriday) {
            $holidays->push($this->make($goodFriday->toDateString(), 'Siklet (Good Friday)', 'religious'));
        }

        // Easter Sunday (Fasika) — calculated
        $easter = $this->orthodoxEaster($year);
        if ($easter) {
            $holidays->push($this->make($easter->toDateString(), 'Fasika (Ethiopian Easter)', 'religious'));
        }

        // International Labour Day — 1 May
        $holidays->push($this->make("{$year}-05-01", 'International Labour Day', 'public'));

        // Ethiopian Patriots' Victory Day — 5 May
        $holidays->push($this->make("{$year}-05-05", "Patriots' Victory Day", 'public'));

        // Downfall of the Derg — 28 May
        $holidays->push($this->make("{$year}-05-28", 'Downfall of the Derg', 'public'));

        // Eid al-Fitr — approximate (varies by moon sighting; we use a fixed approximation)
        // Note: exact date varies. HR can adjust via the UI.
        $eidFitr = $this->approximateEidAlFitr($year);
        if ($eidFitr) {
            $holidays->push($this->make($eidFitr->toDateString(), 'Eid al-Fitr', 'religious'));
        }

        // Eid al-Adha (Arafa) — approximate
        $eidAdha = $this->approximateEidAlAdha($year);
        if ($eidAdha) {
            $holidays->push($this->make($eidAdha->toDateString(), 'Eid al-Adha (Arafa)', 'religious'));
        }

        // Ethiopian New Year (Enkutatash) — 11 September (12 Sep in Ethiopian leap years)
        $enkutatash = $this->enkutatash($year);
        $holidays->push($this->make($enkutatash->toDateString(), 'Enkutatash (Ethiopian New Year)', 'public'));

        // Finding of the True Cross (Meskel) — 27 September
        $holidays->push($this->make("{$year}-09-27", 'Meskel (Finding of the True Cross)', 'religious'));

        // Prophet Muhammad's Birthday (Mawlid) — approximate
        $mawlid = $this->approximateMawlid($year);
        if ($mawlid) {
            $holidays->push($this->make($mawlid->toDateString(), "Prophet Muhammad's Birthday (Mawlid)", 'religious'));
        }

        // Filter to only dates within the requested Gregorian year
        return $holidays->filter(fn($h) => $h['date']->year === $year)
                        ->sortBy(fn($h) => $h['date']->toDateString())
                        ->values();
    }

    /**
     * Check if a given Gregorian date is an Ethiopian public holiday.
     * Uses the database (seeded holidays) for fast lookup.
     *
     * @param  string $date  Y-m-d
     * @return bool
     */
    public function isHoliday(string $date): bool
    {
        return EthiopianHoliday::where('date', $date)->exists();
    }

    /**
     * Get holiday name for a date, or null if not a holiday.
     */
    public function getHolidayName(string $date): ?string
    {
        return EthiopianHoliday::where('date', $date)->value('name');
    }

    /**
     * Get all holiday dates for a year as a flat array of Y-m-d strings.
     * Cached in memory per request.
     */
    public function getHolidayDates(int $year): array
    {
        static $cache = [];
        if (!isset($cache[$year])) {
            $cache[$year] = EthiopianHoliday::where('year', $year)
                ->pluck('date')
                ->map(fn($d) => is_string($d) ? $d : $d->toDateString())
                ->toArray();
        }
        return $cache[$year];
    }

    /**
     * Count working days between two dates, excluding weekends AND holidays.
     *
     * @param  string $from  Y-m-d
     * @param  string $to    Y-m-d
     * @return int
     */
    public function workingDaysBetween(string $from, string $to): int
    {
        $start = Carbon::parse($from);
        $end   = Carbon::parse($to);
        $year  = $start->year;

        // Get holiday dates for the year(s) spanned
        $holidayDates = $this->getHolidayDates($year);
        if ($end->year !== $year) {
            $holidayDates = array_merge($holidayDates, $this->getHolidayDates($end->year));
        }

        $count = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            if (!$current->isWeekend() && !in_array($dateStr, $holidayDates)) {
                $count++;
            }
            $current->addDay();
        }
        return $count;
    }

    /**
     * Seed holidays for a given year into the database.
     * Safe to call multiple times — uses updateOrCreate.
     *
     * @param  int $year
     * @return int  Number of holidays seeded
     */
    public function seedYear(int $year): int
    {
        $holidays = $this->getHolidaysForYear($year);
        $count    = 0;

        foreach ($holidays as $h) {
            EthiopianHoliday::updateOrCreate(
                ['date' => $h['date']->toDateString(), 'name' => $h['name']],
                [
                    'type'    => $h['type'],
                    'is_paid' => true,
                    'year'    => $year,
                ]
            );
            $count++;
        }

        return $count;
    }

    // ── Calendar calculation helpers ─────────────────────────────────────────

    /**
     * Ethiopian Orthodox Easter (Fasika) using the Julian calendar algorithm.
     * Returns Gregorian date.
     *
     * Algorithm: Julian Easter (Meeus/Jones/Butcher) + 13-day Julian→Gregorian offset
     */
    public function orthodoxEaster(int $year): ?Carbon
    {
        // Julian Easter calculation
        $a = $year % 4;
        $b = $year % 7;
        $c = $year % 19;
        $d = (19 * $c + 15) % 30;
        $e = (2 * $a + 4 * $b - $d + 34) % 7;
        $f = (int)(($d + $e + 114) / 31);  // month
        $g = (($d + $e + 114) % 31) + 1;   // day

        // Julian date → Gregorian: add 13 days (20th/21st century offset)
        try {
            $julian   = Carbon::createFromDate($year, $f, $g);
            $gregorian = $julian->addDays(13);
            return $gregorian;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Orthodox Good Friday = Easter - 2 days.
     */
    public function orthodoxGoodFriday(int $year): ?Carbon
    {
        $easter = $this->orthodoxEaster($year);
        return $easter ? $easter->subDays(2) : null;
    }

    /**
     * Enkutatash (Ethiopian New Year).
     * 11 September in most years, 12 September in Ethiopian leap years.
     * Ethiopian leap year: Gregorian year + 1 is divisible by 4.
     */
    public function enkutatash(int $year): Carbon
    {
        // Ethiopian leap year check: the year AFTER the Gregorian year is a leap year
        $isEthiopianLeap = (($year + 1) % 4 === 0);
        $day = $isEthiopianLeap ? 12 : 11;
        return Carbon::createFromDate($year, 9, $day);
    }

    /**
     * Approximate Eid al-Fitr for a Gregorian year.
     * Uses a simplified Hijri→Gregorian conversion.
     * HR should verify and adjust via the UI each year.
     */
    public function approximateEidAlFitr(int $year): ?Carbon
    {
        // Approximate dates (Eid al-Fitr shifts ~11 days earlier each Gregorian year)
        // Base: 2024 = April 10
        $base     = Carbon::createFromDate(2024, 4, 10);
        $diff     = $year - 2024;
        $approx   = $base->copy()->addDays($diff * (-11));
        // Keep within the year
        return $approx->year === $year ? $approx : null;
    }

    /**
     * Approximate Eid al-Adha for a Gregorian year.
     * Base: 2024 = June 17
     */
    public function approximateEidAlAdha(int $year): ?Carbon
    {
        $base   = Carbon::createFromDate(2024, 6, 17);
        $diff   = $year - 2024;
        $approx = $base->copy()->addDays($diff * (-11));
        return $approx->year === $year ? $approx : null;
    }

    /**
     * Approximate Prophet Muhammad's Birthday (Mawlid) for a Gregorian year.
     * Base: 2024 = September 16
     */
    public function approximateMawlid(int $year): ?Carbon
    {
        $base   = Carbon::createFromDate(2024, 9, 16);
        $diff   = $year - 2024;
        $approx = $base->copy()->addDays($diff * (-11));
        return $approx->year === $year ? $approx : null;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function make(string $date, string $name, string $type): array
    {
        return [
            'date' => Carbon::parse($date),
            'name' => $name,
            'type' => $type,
        ];
    }
}
