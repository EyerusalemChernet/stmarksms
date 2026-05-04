<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee — core HR identity entity.
 *
 * This is the central model for the HR module.
 * It has a nullable link to users (auth system) via user_id.
 * All HR sub-modules (payroll, leave, attendance, contracts) will
 * foreign-key to employees.id, NOT to users.id.
 */
class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'phone2',
        'email',
        'address',
        'photo',
        'national_id',
        'tin_number',
        'pension_number',
        'status',
        'termination_date',
        'termination_reason',
        'hr_notes',
    ];

    protected $casts = [
        'date_of_birth'    => 'date',
        'termination_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /** The system login account for this employee (may be null) */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Current job contract / employment terms */
    public function employmentDetails()
    {
        return $this->hasOne(EmploymentDetails::class);
    }

    /** Emergency contacts (multiple allowed) */
    public function emergencyContacts()
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    /** Primary emergency contact */
    public function primaryEmergencyContact()
    {
        return $this->hasOne(EmployeeEmergencyContact::class)->where('is_primary', true);
    }

    /** Academic / professional qualifications */
    public function qualifications()
    {
        return $this->hasMany(EmployeeQualification::class)->orderByDesc('graduation_year');
    }

    /** Salary history */
    public function salaries()
    {
        return $this->hasMany(StaffSalary::class)->orderByDesc('start_date');
    }

    /** Current active salary */
    public function currentSalary()
    {
        return $this->hasOne(StaffSalary::class)->whereNull('end_date')->latestOfMany('start_date');
    }

    /** Position history */
    public function positionHistory()
    {
        return $this->hasMany(StaffPosition::class)->orderByDesc('start_date');
    }

    /** Current position */
    public function currentPosition()
    {
        return $this->hasOne(StaffPosition::class)
            ->whereNull('end_date')
            ->with('position')
            ->latestOfMany('start_date');
    }

    /** Shift history */
    public function shiftHistory()
    {
        return $this->hasMany(StaffShift::class)->orderByDesc('start_date');
    }

    /** Current shift */
    public function currentShift()
    {
        return $this->hasOne(StaffShift::class)
            ->whereNull('end_date')
            ->with('shift')
            ->latestOfMany('start_date');
    }

    /** Attendance records */
    public function attendances()
    {
        return $this->hasMany(StaffAttendance::class);
    }

    /** Payroll records */
    public function payrolls()
    {
        return $this->hasMany(StaffPayroll::class)->orderByDesc('month');
    }

    /** Leave requests */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class)->orderByDesc('start_date');
    }

    /** Leave balances for the current year */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class)->where('year', now()->year);
    }

    /** Performance reviews */
    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class)->orderByDesc('period');
    }

    // ── Computed attributes ──────────────────────────────────────────────────

    /** Full name */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Photo URL — falls back to user photo, then default */
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) return $this->photo;
        if ($this->user) return $this->user->photo;
        return asset('global_assets/images/user.png');
    }

    /** Department shortcut via employment_details */
    public function getDepartmentAttribute(): ?Department
    {
        return $this->employmentDetails?->department;
    }

    // ── Status helpers ───────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'active'     => 'success',
            'on_leave'   => 'warning',
            'suspended'  => 'danger',
            'terminated' => 'dark',
            default      => 'secondary',
        };
    }

    // ── Code generation ──────────────────────────────────────────────────────

    /**
     * Generate the next employee code: STF-0001, STF-0002 …
     */
    public static function generateCode(): string
    {
        $last = static::withTrashed()->orderByDesc('id')->value('employee_code');
        if (!$last) return 'STF-0001';

        $num = (int) substr($last, 4);
        return 'STF-' . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }
}
