<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeQualification;
use App\Models\EmploymentDetails;
use Illuminate\Support\Facades\DB;

/**
 * EmployeeProfileService
 *
 * Single source of truth for all employee create / update / terminate logic.
 * All operations work on the employees table as the core HR entity.
 * The users table (auth) is touched only when a linked account exists.
 */
class EmployeeProfileService
{
    /**
     * Create a new Employee record and its employment_details row.
     * Optionally links to an existing user account.
     *
     * @param  array $data  Validated data from the request
     * @return Employee
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {

            $employee = Employee::create([
                'user_id'       => $data['user_id'] ?? null,
                'employee_code' => Employee::generateCode(),
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'gender'        => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'phone2'        => $data['phone2'] ?? null,
                'email'         => $data['email'] ?? null,
                'address'       => $data['address'] ?? null,
                'national_id'   => $data['national_id'] ?? null,
                'tin_number'    => $data['tin_number'] ?? null,
                'pension_number'=> $data['pension_number'] ?? null,
                'status'        => 'active',
                'hr_notes'      => $data['hr_notes'] ?? null,
            ]);

            EmploymentDetails::create([
                'employee_id'     => $employee->id,
                'department_id'   => $data['department_id'] ?? null,
                'position_id'     => $data['position_id'] ?? null,
                'employment_type' => $data['employment_type'] ?? 'full_time',
                'hire_date'       => $data['hire_date'] ?? null,
                'contract_end_date' => $data['contract_end_date'] ?? null,
                'currency'        => $data['currency'] ?? 'ETB',
                'salary'          => $data['salary'] ?? 0,
                'is_remote'       => $data['is_remote'] ?? false,
                'bank_name'       => $data['bank_name'] ?? null,
                'bank_account_no' => $data['bank_account_no'] ?? null,
            ]);

            AuditLog::log('created', 'hr', "Employee {$employee->employee_code} ({$employee->full_name}) created");

            // Optionally assign a shift on creation
            if (!empty($data['shift_id'])) {
                \App\Models\StaffShift::create([
                    'employee_id' => $employee->id,
                    'shift_id'    => $data['shift_id'],
                    'start_date'  => $data['hire_date'] ?? now()->toDateString(),
                    'end_date'    => null,
                ]);
            }

            return $employee;
        });
    }

    /**
     * Update employee personal info and employment details.
     *
     * @param  Employee $employee
     * @param  array    $data
     */
    public function update(Employee $employee, array $data): void
    {
        DB::transaction(function () use ($employee, $data) {

            // ── Personal identity ────────────────────────────────────────────
            $employee->update(array_filter([
                'first_name'     => $data['first_name']     ?? $employee->first_name,
                'last_name'      => $data['last_name']      ?? $employee->last_name,
                'gender'         => $data['gender']         ?? $employee->gender,
                'date_of_birth'  => $data['date_of_birth']  ?? $employee->date_of_birth,
                'phone'          => $data['phone']          ?? $employee->phone,
                'phone2'         => $data['phone2']         ?? $employee->phone2,
                'email'          => $data['email']          ?? $employee->email,
                'address'        => $data['address']        ?? $employee->address,
                'national_id'    => $data['national_id']    ?? $employee->national_id,
                'tin_number'     => $data['tin_number']     ?? $employee->tin_number,
                'pension_number' => $data['pension_number'] ?? $employee->pension_number,
                'hr_notes'       => $data['hr_notes']       ?? $employee->hr_notes,
            ], fn($v) => $v !== null));

            // ── Employment details ───────────────────────────────────────────
            $employment = $employee->employmentDetails ?? new EmploymentDetails(['employee_id' => $employee->id]);
            $employment->fill(array_filter([
                'department_id'     => $data['department_id']     ?? null,
                'position_id'       => $data['position_id']       ?? null,
                'reporting_manager_id' => $data['reporting_manager_id'] ?? null,
                'employment_type'   => $data['employment_type']   ?? null,
                'hire_date'         => $data['hire_date']         ?? null,
                'contract_end_date' => $data['contract_end_date'] ?? null,
                'currency'          => $data['currency']          ?? null,
                'salary'            => $data['salary']            ?? null,
                'is_remote'         => $data['is_remote']         ?? null,
                'bank_name'         => $data['bank_name']         ?? null,
                'bank_account_no'   => $data['bank_account_no']   ?? null,
            ], fn($v) => $v !== null));
            $employment->save();

            AuditLog::log('updated', 'hr', "Employee {$employee->employee_code} profile updated");
        });
    }

    /**
     * Terminate an employee. Requires a date and reason.
     */
    public function terminate(Employee $employee, string $date, string $reason): void
    {
        DB::transaction(function () use ($employee, $date, $reason) {
            $employee->update([
                'status'             => 'terminated',
                'termination_date'   => $date,
                'termination_reason' => $reason,
            ]);

            AuditLog::log(
                'updated', 'hr',
                "Employee {$employee->employee_code} terminated on {$date}. Reason: {$reason}"
            );
        });
    }

    /**
     * Reactivate a terminated or suspended employee.
     */
    public function reactivate(Employee $employee): void
    {
        $employee->update([
            'status'             => 'active',
            'termination_date'   => null,
            'termination_reason' => null,
        ]);

        AuditLog::log('updated', 'hr', "Employee {$employee->employee_code} reactivated");
    }

    /**
     * Change status to active / on_leave / suspended.
     * Use terminate() for terminations — it requires a reason.
     */
    public function changeStatus(Employee $employee, string $status): void
    {
        if ($status === 'terminated') {
            throw new \InvalidArgumentException('Use terminate() for terminations.');
        }

        $employee->update(['status' => $status]);

        AuditLog::log('updated', 'hr', "Employee {$employee->employee_code} status → {$status}");
    }

    /**
     * Sync emergency contacts.
     * Replaces all existing contacts with the provided array.
     *
     * @param  Employee $employee
     * @param  array    $contacts  [ ['name'=>..., 'phone'=>..., 'relationship'=>..., 'is_primary'=>...], ... ]
     */
    public function syncEmergencyContacts(Employee $employee, array $contacts): void
    {
        $employee->emergencyContacts()->delete();

        foreach ($contacts as $contact) {
            if (empty($contact['name']) || empty($contact['phone'])) continue;

            EmployeeEmergencyContact::create([
                'employee_id'  => $employee->id,
                'name'         => $contact['name'],
                'phone'        => $contact['phone'],
                'relationship' => $contact['relationship'] ?? null,
                'is_primary'   => $contact['is_primary'] ?? false,
            ]);
        }
    }

    /**
     * Add a qualification record.
     */
    public function addQualification(Employee $employee, array $data): EmployeeQualification
    {
        return EmployeeQualification::create([
            'employee_id'     => $employee->id,
            'degree'          => $data['degree'],
            'field_of_study'  => $data['field_of_study'] ?? null,
            'institution'     => $data['institution'] ?? null,
            'graduation_year' => $data['graduation_year'] ?? null,
        ]);
    }

    /**
     * Delete a qualification.
     */
    public function deleteQualification(int $qualificationId, Employee $employee): void
    {
        EmployeeQualification::where('id', $qualificationId)
            ->where('employee_id', $employee->id)
            ->delete();
    }
}
