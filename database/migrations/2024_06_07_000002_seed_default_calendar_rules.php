<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the default Ethiopian academic calendar rules.
 * Ethiopian academic year runs roughly September → July (Meskerem → Sene).
 */
class SeedDefaultCalendarRules extends Migration
{
    public function up()
    {
        $rules = [
            // ── School open/close ─────────────────────────────────────────────
            [
                'name'        => 'First Day of School',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'event',
                'rule_value'  => json_encode(['month' => 9, 'day' => 11]), // Sep 11 (Meskerem 1)
                'color'       => '#10b981',
                'sort_order'  => 1,
                'description' => 'Ethiopian New Year / First day of academic year (Meskerem 1)',
            ],
            [
                'name'        => 'Last Day of School',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'event',
                'rule_value'  => json_encode(['month' => 7, 'day' => 7]),  // Jul 7
                'color'       => '#10b981',
                'sort_order'  => 2,
                'description' => 'End of academic year',
            ],

            // ── Semester structure ────────────────────────────────────────────
            [
                'name'        => 'Semester 1 Start',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'event',
                'rule_value'  => json_encode(['month' => 9, 'day' => 11]),
                'color'       => '#4f46e5',
                'sort_order'  => 3,
                'description' => 'First semester begins',
            ],
            [
                'name'        => 'Semester 1 Midterm Exams',
                'rule_type'   => 'week_offset_from_start',
                'event_type'  => 'exam',
                'rule_value'  => json_encode(['weeks' => 8, 'duration_days' => 5]),
                'color'       => '#f59e0b',
                'sort_order'  => 4,
                'description' => '8 weeks after semester 1 start',
            ],
            [
                'name'        => 'Semester 1 Final Exams',
                'rule_type'   => 'week_offset_from_start',
                'event_type'  => 'exam',
                'rule_value'  => json_encode(['weeks' => 16, 'duration_days' => 7]),
                'color'       => '#ef4444',
                'sort_order'  => 5,
                'description' => '16 weeks after semester 1 start',
            ],
            [
                'name'        => 'Semester 1 End / Winter Break Start',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'break',
                'rule_value'  => json_encode(['month' => 1, 'day' => 7]),  // Jan 7 (Genna)
                'color'       => '#8b5cf6',
                'sort_order'  => 6,
                'description' => 'End of first semester — Genna (Ethiopian Christmas) break',
            ],
            [
                'name'        => 'Semester 2 Start',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'event',
                'rule_value'  => json_encode(['month' => 1, 'day' => 21]),
                'color'       => '#4f46e5',
                'sort_order'  => 7,
                'description' => 'Second semester begins after Genna break',
            ],
            [
                'name'        => 'Semester 2 Midterm Exams',
                'rule_type'   => 'week_offset_from_sem2',
                'event_type'  => 'exam',
                'rule_value'  => json_encode(['weeks' => 8, 'duration_days' => 5]),
                'color'       => '#f59e0b',
                'sort_order'  => 8,
                'description' => '8 weeks after semester 2 start',
            ],
            [
                'name'        => 'Semester 2 Final Exams',
                'rule_type'   => 'week_offset_from_sem2',
                'event_type'  => 'exam',
                'rule_value'  => json_encode(['weeks' => 16, 'duration_days' => 7]),
                'color'       => '#ef4444',
                'sort_order'  => 9,
                'description' => '16 weeks after semester 2 start',
            ],

            // ── Ethiopian public holidays ─────────────────────────────────────
            [
                'name'        => 'Ethiopian New Year (Enkutatash)',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['month' => 9, 'day' => 11]),
                'color'       => '#ef4444',
                'sort_order'  => 10,
                'description' => 'Meskerem 1 — Ethiopian New Year',
            ],
            [
                'name'        => 'Meskel (Finding of the True Cross)',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['month' => 9, 'day' => 27]),
                'color'       => '#ef4444',
                'sort_order'  => 11,
                'description' => 'Meskerem 17 — Meskel holiday',
            ],
            [
                'name'        => 'Genna (Ethiopian Christmas)',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['month' => 1, 'day' => 7]),
                'color'       => '#ef4444',
                'sort_order'  => 12,
                'description' => 'Tahsas 29 — Ethiopian Christmas',
            ],
            [
                'name'        => 'Timkat (Ethiopian Epiphany)',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['month' => 1, 'day' => 19]),
                'color'       => '#ef4444',
                'sort_order'  => 13,
                'description' => 'Tir 11 — Timkat',
            ],
            [
                'name'        => 'Adwa Victory Day',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['month' => 3, 'day' => 2]),
                'color'       => '#ef4444',
                'sort_order'  => 14,
                'description' => 'Yekatit 23 — Battle of Adwa',
            ],
            [
                'name'        => 'Ethiopian Good Friday (Siklet)',
                'rule_type'   => 'easter_offset',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['offset_days' => -2, 'calendar' => 'orthodox']),
                'color'       => '#ef4444',
                'sort_order'  => 15,
                'description' => 'Orthodox Good Friday — 2 days before Easter',
            ],
            [
                'name'        => 'Ethiopian Easter (Fasika)',
                'rule_type'   => 'easter_offset',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['offset_days' => 0, 'calendar' => 'orthodox']),
                'color'       => '#ef4444',
                'sort_order'  => 16,
                'description' => 'Orthodox Easter (Fasika)',
            ],
            [
                'name'        => 'International Labour Day',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['month' => 5, 'day' => 1]),
                'color'       => '#ef4444',
                'sort_order'  => 17,
                'description' => 'May 1 — Labour Day',
            ],
            [
                'name'        => 'Patriots Victory Day',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['month' => 5, 'day' => 5]),
                'color'       => '#ef4444',
                'sort_order'  => 18,
                'description' => 'May 5 — Liberation Day',
            ],
            [
                'name'        => 'Derg Downfall Day',
                'rule_type'   => 'fixed_month_day',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['month' => 5, 'day' => 28]),
                'color'       => '#ef4444',
                'sort_order'  => 19,
                'description' => 'May 28 — Fall of the Derg',
            ],
            [
                'name'        => 'Eid al-Fitr',
                'rule_type'   => 'islamic_holiday',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['holiday' => 'eid_al_fitr', 'duration_days' => 2]),
                'color'       => '#ef4444',
                'sort_order'  => 20,
                'description' => 'End of Ramadan — date varies yearly',
            ],
            [
                'name'        => 'Eid al-Adha (Arafa)',
                'rule_type'   => 'islamic_holiday',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['holiday' => 'eid_al_adha', 'duration_days' => 2]),
                'color'       => '#ef4444',
                'sort_order'  => 21,
                'description' => 'Feast of Sacrifice — date varies yearly',
            ],
            [
                'name'        => 'Mawlid al-Nabi',
                'rule_type'   => 'islamic_holiday',
                'event_type'  => 'holiday',
                'rule_value'  => json_encode(['holiday' => 'mawlid', 'duration_days' => 1]),
                'color'       => '#ef4444',
                'sort_order'  => 22,
                'description' => 'Prophet\'s Birthday — date varies yearly',
            ],
        ];

        $now = now();
        foreach ($rules as &$r) {
            $r['is_active']   = true;
            $r['notify_email']= false;
            $r['created_at']  = $now;
            $r['updated_at']  = $now;
        }

        DB::table('calendar_rules')->insert($rules);
    }

    public function down()
    {
        DB::table('calendar_rules')->truncate();
    }
}
