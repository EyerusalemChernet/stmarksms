<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AcademicYear extends Model
{
    protected $table = 'academic_years';

    protected $fillable = [
        'name', 'eth_name', 'start_date', 'end_date',
        'status', 'is_current', 'generated_by', 'published_at',
        'is_active',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'is_current'   => 'boolean',
        'is_active'    => 'boolean',
        'published_at' => 'datetime',
    ];

    // ── Original Academic Calendar relationships ─────────────────────────────

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

    // ── New Promotion/Enrollment relationships ────────────────────────────────

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'academic_year_id');
    }

    public function promotionBatchesFrom()
    {
        return $this->hasMany(PromotionBatch::class, 'from_academic_year_id');
    }

    // ── Query scopes ──────────────────────────────────────────────────────────

    /** Original scope: active by status field */
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    /** Original scope: current by is_current flag */
    public function scopeCurrent($q)
    {
        return $q->where('is_current', true);
    }

    // ── Methods ───────────────────────────────────────────────────────────────

    /**
     * Activate this year for the promotion module:
     * sets is_active=1 and is_current=1, deactivates all others.
     */
    public function activate(): void
    {
        DB::table('academic_years')->update(['is_active' => 0, 'is_current' => 0]);
        DB::table('academic_years')->where('id', $this->id)->update([
            'is_active'  => 1,
            'is_current' => 1,
            'status'     => 'active',
        ]);
        $this->is_active  = true;
        $this->is_current = true;
    }

    /** Ethiopian year label from Gregorian start year. */
    public static function ethYearLabel(int $gcYear): string
    {
        $etStart = $gcYear - 7;
        return $etStart . '/' . ($etStart + 1 - 2000) . ' E.C.';
    }

    /** Accessor: year_name alias for the name column */
    public function getYearNameAttribute(): string
    {
        return $this->name;
    }
}
