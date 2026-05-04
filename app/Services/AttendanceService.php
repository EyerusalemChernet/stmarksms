<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * AttendanceService
 *
 * All attendance business logic lives here.
 * The controller validates input and calls this service.
 * No DB queries in the controller for attendance.
 */
class AttendanceService
{
    /**
     * Grace period in minutes before an employee is marked "late".
     * e.g. shift starts 08:00, grace = 15 → late if sign-in after 08:15
     */
    private const LATE_GRACE_MINUTES = 15;

    // ── Core save ────────────────────────────────────────────────────────────

    /**
     * Save or update a single attendance record.
     * Automatically calculates: late_minutes, actual_hours, overtime_hours, status.
     *
     * @param  int    $employeeId
     * @param  string $date        Y-m-d
     * @param  array  $data        Keys: status, sign_in_time, sign_off_time, leave_type, remark
     * @return StaffAttendance
     */
    public function save(int $employeeId, string $date, array $data): StaffAttendance
    {
        $employee   = Employee::with('currentShift.shift')->find($employeeId);
        $shift      = $employee?->currentShift?->shift;

        $status     = $data['status'] ?? 'present';
        $signIn     = $data['sign_in_time']  ?? null;
        $signOff    = $data['sign_off_time'] ?? null;
        $leaveType  = $data['leave_type']    ?? null;
        $remark     = $data['remark']        ?? null;

        // ── Calculate late_minutes ───────────────────────────────────────────
        $lateMinutes = 0;
        if ($signIn && $shift && in_array($status, ['present', 'late'])) {
            $shiftStart  = Carbon::parse($shift->start_time);
            $actualStart = Carbon::parse($signIn);
            $diff        = $shiftStart->diffInMinutes($actualStart, false); // positive = late
            if ($diff > self::LATE_GRACE_MINUTES) {
                $lateMinutes = (int) $diff;
                $status      = 'late'; // auto-upgrade status
            }
        }

        // ── Calculate actual_hours ───────────────────────────────────────────
        $actualHours = null;
        if ($signIn && $signOff) {
            $in  = Carbon::parse($signIn);
            $out = Carbon::parse($signOff);
            if ($out->lessThan($in)) $out->addDay(); // overnight
            $actualHours = round($in->diffInMinutes($out) / 60, 2);
        }

        // ── Calculate expected_hours from shift ──────────────────────────────
        $expectedHours = $shift ? $shift->durationHours() : null;

        // ── Calculate overtime ───────────────────────────────────────────────
        $overtimeHours = 0;
        if ($actualHours !== null && $expectedHours !== null) {
            $diff = $actualHours - $expectedHours;
            $overtimeHours = $diff > 0 ? round($diff, 2) : 0;
        }

        return StaffAttendance::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date],
            [
                'user_id'            => $employee?->user_id,  // keep legacy column populated
                'status'             => $status,
                'leave_type'         => $status === 'leave' ? $leaveType : null,
                'sign_in_time'       => $signIn,
                'sign_off_time'      => $signOff,
                'expected_hours'     => $expectedHours,
                'actual_hours'       => $actualHours,
                'overtime_hours'     => $overtimeHours,
                'late_minutes'       => $lateMinutes,
                'is_manually_filled' => true,
                'remark'             => $remark,
            ]
        );
    }

    /**
     * Bulk save attendance for multiple employees on one date.
     *
     * @param  string $date
     * @param  array  $attendanceData  [ employee_id => ['status'=>..., 'sign_in_time'=>..., ...] ]
     * @param  array  $remarks         [ employee_id => string ]
     * @return int    Number of records saved
     */
    public function saveBulk(string $date, array $attendanceData, array $remarks = []): int
    {
        $count = 0;
        foreach ($attendanceData as $employeeId => $data) {
            $data['remark'] = $remarks[$employeeId] ?? null;
            $this->save((int) $employeeId, $date, $data);
            $count++;
        }
        return $count;
    }

    // ── Summary & reporting ──────────────────────────────────────────────────

    /**
     * Monthly attendance summary for a single employee.
     *
     * Returns:
     *   present, late, absent, leave, total_days,
     *   attendance_rate (%), total_actual_hours, total_overtime_hours
     *
     * @param  int    $employeeId
     * @param  string $month       Y-m  (e.g. "2024-07")
     * @return array
     */
    public function monthlySummary(int $employeeId, string $month): array
    {
        $records = StaffAttendance::where('employee_id', $employeeId)
            ->where('date', 'like', $month . '%')
            ->get();

        $present  = $records->where('status', 'present')->count();
        $late     = $records->where('status', 'late')->count();
        $absent   = $records->where('status', 'absent')->count();
        $leave    = $records->where('status', 'leave')->count();
        $total    = $records->count();

        $attendanceRate = $total > 0
            ? round((($present + $late) / $total) * 100, 1)
            : 0;

        return [
            'present'             => $present,
            'late'                => $late,
            'absent'              => $absent,
            'leave'               => $leave,
            'total_days'          => $total,
            'attendance_rate'     => $attendanceRate,
            'total_actual_hours'  => round($records->sum('actual_hours'), 2),
            'total_overtime_hours'=> round($records->sum('overtime_hours'), 2),
            'total_late_minutes'  => $records->sum('late_minutes'),
        ];
    }

    /**
     * Monthly summary for ALL active employees — used by payroll and HR dashboard.
     *
     * @param  string $month  Y-m
     * @return Collection  keyed by employee_id
     */
    public function allEmployeesMonthlySummary(string $month): Collection
    {
        $records = StaffAttendance::where('date', 'like', $month . '%')
            ->whereNotNull('employee_id')
            ->get()
            ->groupBy('employee_id');

        return $records->map(function ($group) {
            $present = $group->where('status', 'present')->count();
            $late    = $group->where('status', 'late')->count();
            $total   = $group->count();
            return [
                'present'         => $present,
                'late'            => $late,
                'absent'          => $group->where('status', 'absent')->count(),
                'leave'           => $group->where('status', 'leave')->count(),
                'total_days'      => $total,
                'attendance_rate' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0,
                'actual_hours'    => round($group->sum('actual_hours'), 2),
                'overtime_hours'  => round($group->sum('overtime_hours'), 2),
            ];
        });
    }

    /**
     * Get attendance records for a date range with optional employee filter.
     *
     * @param  string   $from        Y-m-d
     * @param  string   $to          Y-m-d
     * @param  int|null $employeeId  null = all employees
     * @return Collection
     */
    public function getRange(string $from, string $to, ?int $employeeId = null): Collection
    {
        return StaffAttendance::with('employee')
            ->whereBetween('date', [$from, $to])
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->orderBy('date')
            ->get();
    }

    /**
     * Attendance rate for an employee over the last N days.
     * Used for the employee profile card.
     *
     * @param  int $employeeId
     * @param  int $days
     * @return array  ['present'=>int, 'total'=>int, 'rate'=>float]
     */
    public function recentRate(int $employeeId, int $days = 30): array
    {
        $from    = now()->subDays($days)->toDateString();
        $records = StaffAttendance::where('employee_id', $employeeId)
            ->where('date', '>=', $from)
            ->get();

        $present = $records->whereIn('status', ['present', 'late'])->count();
        $total   = $records->count();

        return [
            'present' => $present,
            'absent'  => $total - $present,
            'total'   => $total,
            'rate'    => $total > 0 ? round(($present / $total) * 100, 1) : 100,
        ];
    }
}
