<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = [
        'title', 'description', 'start_date', 'end_date', 'start_time', 'end_time',
        'color', 'type', 'all_day', 'notify_email', 'notify_roles', 'google_event_id', 'created_by',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'all_day'      => 'boolean',
        'notify_email' => 'boolean',
    ];

    // Ethiopian month names (Ge'ez calendar)
    public static $ethiopianMonths = [
        1  => 'Meskerem', 2  => 'Tikimt',   3  => 'Hidar',
        4  => 'Tahsas',   5  => 'Tir',      6  => 'Yekatit',
        7  => 'Megabit',  8  => 'Miazia',   9  => 'Ginbot',
        10 => 'Sene',     11 => 'Hamle',    12 => 'Nehase',
        13 => 'Pagume',
    ];

    // Convert Gregorian date to Ethiopian date (approximate)
    public static function toEthiopian(\DateTime $date): array
    {
        $jdn = self::gregorianToJdn($date->format('Y'), $date->format('n'), $date->format('j'));
        return self::jdnToEthiopian($jdn);
    }

    private static function gregorianToJdn(int $y, int $m, int $d): int
    {
        $a = intdiv(14 - $m, 12);
        $y2 = $y + 4800 - $a;
        $m2 = $m + 12 * $a - 3;
        return $d + intdiv(153 * $m2 + 2, 5) + 365 * $y2 + intdiv($y2, 4) - intdiv($y2, 100) + intdiv($y2, 400) - 32045;
    }

    private static function jdnToEthiopian(int $jdn): array
    {
        // Ethiopian epoch JDN = 1724221 (Meskerem 1, 1 E.C. = Aug 29, 8 AD Julian)
        $r = ($jdn - 1724221) % 1461;
        if ($r < 0) $r += 1461;
        $n     = $r % 365 + 365 * intdiv($r, 1460);
        $year  = 4 * intdiv($jdn - 1724221, 1461) + intdiv($r, 365) - intdiv($r, 1460);
        $month = intdiv($n, 30) + 1;
        $day   = $n % 30 + 1;
        return ['year' => $year, 'month' => $month, 'day' => $day];
    }
}
