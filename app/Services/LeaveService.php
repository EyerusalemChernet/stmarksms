<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * LeaveService
 *
 * Handles all leave management logic:
 *   - Calculating working days in a date range (excludes weekends)
 *   - Creating leave requests and updating balances
 *   - Approving / rejecting requests
 *   - Auto-creating attendance records when leave is approved
 *   - Initialising leave balances from policies
 */
class LeaveService
{
    // Days considered weekend (0=Sunday, 6=Saturday)
    private const WEEKEND_DAYS = [0, 6];

    // ── Working days calculation ─────────────────────────────────────────────

    /**
     * Count working days between two dates (excludes Sat/Sun).
     */
    public function workingDaysBetween(string $from, string $to): int
    {
        $period = CarbonPeriod::create($from, $to);
        $count  = 0;
        foreach ($period as $day) {
            if (!in_array($day->dayOfWeek, self::WEEKEND_DAYS)) {
                $count++;
            }
        }
        return $count;
    }

    // ── Balance management ───────────────────────────────────────────────────

    /**
     * Initialise leave balances for an employee for a given year.
     * Creates one LeaveBalance row per leave type defined in leave_policies.
     * Safe to call multiple times — uses firstOrCreate.
     */
    public function initialiseBalances(Employee $employee, int $year): void
    {
        $policies = LeavePolicy::where('year', $year)->get();

        foreach ($policies as $policy) {
            LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type'  => $policy->leave_type,
                    'year'        => $year,
                ],
                [
                    'entitled' => $policy->days_entitled,
                    'used'     => 0,
                    'pending'  => 0,
                ]
            );
        }
    }

    /**
     * Initialise balances for ALL active employees for a given year.
     * Called at year start or when a new policy is created.
     */
    public function initialiseAllBalances(int $year): int
    {
        $employees = Employee::where('status', 'active')->get();
        foreach ($employees as $emp) {
            $this->initialiseBalances($emp, $year);
        }
        return $employees->count();
    }

    /**
     * Get or create a leave balance row for an employee.
     */
    public function getBalance(Employee $employee, string $leaveType, int $year): LeaveBalance
    {
        $policy = LeavePolicy::where('leave_type', $leaveType)->where('year', $year)->first();

        return LeaveBalance::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type' => $leaveType, 'year' => $year],
            ['entitled' => $policy?->days_entitled ?? 0, 'used' => 0, 'pending' => 0]
        );
    }

    // ── Request lifecycle ────────────────────────────────────────────────────

    /**
     * Submit a new leave request.
     * Validates balance availability and creates the request.
     *
     * @param  Employee $employee
     * @param  array    $data  Keys: leave_type, start_date, end_date, reason
     * @return LeaveRequest
     * @throws \RuntimeException if insufficient balance
     */
    public function submit(Employee $employee, array $data): LeaveRequest
    {
        $leaveType = $data['leave_type'];
        $startDate = $data['start_date'];
        $endDate   = $data['end_date'];
        $year      = Carbon::parse($startDate)->year;

        $daysRequested = $this->workingDaysBetween($startDate, $endDate);

        if ($daysRequested <= 0) {
            throw new \RuntimeException('The selected date range contains no working days.');
        }

        // Check balance (skip check for unpaid leave)
        if ($leaveType !== 'unpaid') {
            $balance = $this->getBalance($employee, $leaveType, $year);
            if ($balance->available < $daysRequested) {
                throw new \RuntimeException(
                    "Insufficient {$leaveType} leave balance. Available: {$balance->available} day(s), Requested: {$daysRequested} day(s)."
                );
            }
        }

        return DB::transaction(function () use ($employee, $leaveType, $startDate, $endDate, $daysRequested, $data, $year) {
            $request = LeaveRequest::create([
                'employee_id'    => $employee->id,
                'leave_type'     => $leaveType,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'days_requested' => $daysRequested,
                'reason'         => $data['reason'] ?? null,
                'status'         => 'pending',
            ]);

            // Increment pending balance
            if ($leaveType !== 'unpaid') {
                LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type', $leaveType)
                    ->where('year', $year)
                    ->increment('pending', $daysRequested);
            }

            AuditLog::log('created', 'hr',
                "Leave request #{$request->id} submitted by employee {$employee->employee_code} — {$leaveType} {$daysRequested} day(s)"
            );

            return $request;
        });
    }

    /**
     * Approve a leave request.
     * - Updates request status
     * - Moves balance from pending → used
     * - Creates attendance records for each working day
     *
     * @param  LeaveRequest $request
     * @param  int          $reviewedByUserId
     * @param  string|null  $comment
     */
    public function approve(LeaveRequest $request, int $reviewedByUserId, ?string $comment = null): void
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Only pending requests can be approved.');
        }

        DB::transaction(function () use ($request, $reviewedByUserId, $comment) {
            $year = $request->start_date->year;

            // Update request
            $request->update([
                'status'         => 'approved',
                'reviewed_by'    => $reviewedByUserId,
                'reviewed_at'    => now(),
                'review_comment' => $comment,
            ]);

            // Update balance: pending → used
            if ($request->leave_type !== 'unpaid') {
                LeaveBalance::where('employee_id', $request->employee_id)
                    ->where('leave_type', $request->leave_type)
                    ->where('year', $year)
                    ->decrement('pending', $request->days_requested);

                LeaveBalance::where('employee_id', $request->employee_id)
                    ->where('leave_type', $request->leave_type)
                    ->where('year', $year)
                    ->increment('used', $request->days_requested);
            }

            // Create attendance records for each working day in the range
            $this->createAttendanceRecords($request, $reviewedByUserId);

            AuditLog::log('updated', 'hr',
                "Leave request #{$request->id} approved by user {$reviewedByUserId}"
            );
        });
    }

    /**
     * Reject a leave request.
     * Releases the pending balance.
     */
    public function reject(LeaveRequest $request, int $reviewedByUserId, ?string $comment = null): void
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Only pending requests can be rejected.');
        }

        DB::transaction(function () use ($request, $reviewedByUserId, $comment) {
            $year = $request->start_date->year;

            $request->update([
                'status'         => 'rejected',
                'reviewed_by'    => $reviewedByUserId,
                'reviewed_at'    => now(),
                'review_comment' => $comment,
            ]);

            // Release pending balance
            if ($request->leave_type !== 'unpaid') {
                LeaveBalance::where('employee_id', $request->employee_id)
                    ->where('leave_type', $request->leave_type)
                    ->where('year', $year)
                    ->decrement('pending', $request->days_requested);
            }

            AuditLog::log('updated', 'hr',
                "Leave request #{$request->id} rejected by user {$reviewedByUserId}"
            );
        });
    }

    /**
     * Cancel a leave request (by HR or the employee themselves).
     * If approved, reverses the attendance records and restores balance.
     */
    public function cancel(LeaveRequest $request, int $cancelledByUserId): void
    {
        if ($request->isCancelled()) {
            throw new \RuntimeException('Request is already cancelled.');
        }

        DB::transaction(function () use ($request, $cancelledByUserId) {
            $year      = $request->start_date->year;
            $wasPending  = $request->isPending();
            $wasApproved = $request->isApproved();

            $request->update(['status' => 'cancelled']);

            if ($wasPending && $request->leave_type !== 'unpaid') {
                // Release pending balance
                LeaveBalance::where('employee_id', $request->employee_id)
                    ->where('leave_type', $request->leave_type)
                    ->where('year', $year)
                    ->decrement('pending', $request->days_requested);
            }

            if ($wasApproved) {
                // Restore used balance
                if ($request->leave_type !== 'unpaid') {
                    LeaveBalance::where('employee_id', $request->employee_id)
                        ->where('leave_type', $request->leave_type)
                        ->where('year', $year)
                        ->decrement('used', $request->days_requested);
                }

                // Remove attendance records created by this leave
                $this->removeAttendanceRecords($request);
            }

            AuditLog::log('updated', 'hr',
                "Leave request #{$request->id} cancelled by user {$cancelledByUserId}"
            );
        });
    }

    // ── Attendance integration ───────────────────────────────────────────────

    /**
     * Create attendance records for each working day in the leave period.
     * Uses updateOrCreate so it won't duplicate if run twice.
     */
    private function createAttendanceRecords(LeaveRequest $request, int $approvedByUserId): void
    {
        $employee = $request->employee;
        $period   = CarbonPeriod::create($request->start_date, $request->end_date);

        foreach ($period as $day) {
            if (in_array($day->dayOfWeek, self::WEEKEND_DAYS)) continue;

            StaffAttendance::updateOrCreate(
                ['employee_id' => $request->employee_id, 'date' => $day->toDateString()],
                [
                    'user_id'            => $employee?->user_id,
                    'status'             => 'leave',
                    'leave_type'         => $request->leave_type,
                    'is_manually_filled' => true,
                    'approved_by'        => $approvedByUserId,
                    'remark'             => "Leave Request #{$request->id}",
                ]
            );
        }
    }

    /**
     * Remove attendance records that were created by a leave request.
     * Only removes records that still have the leave remark — won't touch
     * records that were manually changed after approval.
     */
    private function removeAttendanceRecords(LeaveRequest $request): void
    {
        $period = CarbonPeriod::create($request->start_date, $request->end_date);

        foreach ($period as $day) {
            if (in_array($day->dayOfWeek, self::WEEKEND_DAYS)) continue;

            StaffAttendance::where('employee_id', $request->employee_id)
                ->where('date', $day->toDateString())
                ->where('status', 'leave')
                ->where('remark', "Leave Request #{$request->id}")
                ->delete();
        }
    }

    // ── Reporting ────────────────────────────────────────────────────────────

    /**
     * Get all leave balances for an employee for a given year,
     * merged with policy entitlements.
     *
     * @return array  [ leave_type => ['entitled'=>, 'used'=>, 'pending'=>, 'available'=>] ]
     */
    public function employeeBalanceSummary(Employee $employee, int $year): array
    {
        $leaveTypes = ['annual', 'sick', 'maternity', 'paternity', 'unpaid', 'other'];
        $balances   = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $year)->get()->keyBy('leave_type');

        $policies = LeavePolicy::where('year', $year)->get()->keyBy('leave_type');

        $summary = [];
        foreach ($leaveTypes as $type) {
            $bal = $balances->get($type);
            $pol = $policies->get($type);
            $entitled = $bal?->entitled ?? $pol?->days_entitled ?? 0;
            $used     = $bal?->used     ?? 0;
            $pending  = $bal?->pending  ?? 0;

            $summary[$type] = [
                'label'     => (new LeaveRequest(['leave_type' => $type]))->leaveTypeLabel(),
                'entitled'  => $entitled,
                'used'      => $used,
                'pending'   => $pending,
                'available' => max(0, $entitled - $used - $pending),
                'is_paid'   => $pol?->is_paid ?? ($type !== 'unpaid'),
            ];
        }

        return $summary;
    }
}
