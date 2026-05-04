<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'name', 'eth_name', 'start_date', 'end_date',
        'status', 'is_current', 'generated_by', 'published_at',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'is_current'   => 'boolean',
        'published_at' => 'datetime',
    ];

    public function events()
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function conflicts()
    {
        return $this->hasMany(CalendarConflict::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeCurrent($q)
    {
        return $q->where('is_current', true);
    }

    /** Ethiopian year label from Gregorian start year.
     *  Sep 11 of gcYear = Meskerem 1 of ET year (gcYear - 7).
     *  e.g. GC 2026 → ET 2019/20 E.C.
     */
    public static function ethYearLabel(int $gcYear): string
    {
        $etStart = $gcYear - 7;
        return $etStart . '/' . ($etStart + 1 - 2000) . ' E.C.';
    }
}
