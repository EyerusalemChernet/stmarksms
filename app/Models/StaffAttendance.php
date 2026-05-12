<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',           // legacy — kept for backward compatibility
        'date',
        'status',
        'leave_type',
        'sign_in_time',
        'sign_off_time',
        'expected_hours',
        'actual_hours',
        'overtime_hours',
        'late_minutes',
        'is_manually_filled',
        'approved_by',
        'remark',
    ];

    protected $casts = [
        'is_manually_filled' => 'boolean',
        'expected_hours'     => 'float',
        'actual_hours'       => 'float',
        'overtime_hours'     => 'float',
        'late_minutes'       => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Status helpers ───────────────────────────────────────────────────────

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'present' => 'success',
            'late'    => 'warning',
            'absent'  => 'danger',
            'leave'   => 'info',
            default   => 'secondary',
        };
    }

    public function isPresent(): bool
    {
        return in_array($this->status, ['present', 'late']);
    }

    // ── Computed helpers ─────────────────────────────────────────────────────

    /**
     * Calculate actual hours worked from sign_in and sign_off times.
     * Returns null if either time is missing.
     */
    public function calculateActualHours(): ?float
    {
        if (!$this->sign_in_time || !$this->sign_off_time) return null;

        $in  = Carbon::parse($this->sign_in_time);
        $out = Carbon::parse($this->sign_off_time);

        // Handle overnight shifts
        if ($out->lessThan($in)) $out->addDay();

        return round($in->diffInMinutes($out) / 60, 2);
    }

    /**
     * Human-readable leave type label.
     */
    public function leaveTypeLabel(): string
    {
        return match($this->leave_type) {
            'annual'    => 'Annual Leave',
            'sick'      => 'Sick Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'unpaid'    => 'Unpaid Leave',
            'other'     => 'Other Leave',
            default     => '—',
        };
    }

    /**
     * Formatted late duration string.
     */
    public function lateLabel(): string
    {
        if ($this->late_minutes <= 0) return '';
        if ($this->late_minutes < 60) return "{$this->late_minutes}m late";
        $h = intdiv($this->late_minutes, 60);
        $m = $this->late_minutes % 60;
        return $m > 0 ? "{$h}h {$m}m late" : "{$h}h late";
    }
}
