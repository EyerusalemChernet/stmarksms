<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * HR Architecture Refactor
 *
 * Creates proper HRMS core tables:
 *   employees            — HR identity record (1-to-1 with users)
 *   employment_details   — Contract / job terms (1-to-1 with employees)
 *   employee_emergency_contacts — (1-to-many with employees)
 *   employee_qualifications     — (1-to-many with employees)
 *
 * Also cleans up Step 1's columns that were incorrectly added to staff_records.
 *
 * departments and positions tables already exist — left untouched.
 * staff_records is kept for backward compatibility with the school system.
 * All new HR foreign keys point to employees.id, NOT users.id.
 */
class CreateHrCoreTables extends Migration
{
    public function up(): void
    {
        // ── 1. employees — core HR identity ─────────────────────────────────
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Link to auth system — nullable so HR can create employees
            // before a system account exists (e.g. non-teaching staff)
            $table->unsignedInteger('user_id')->nullable()->unique();

            // Auto-generated code: STF-0001, STF-0002 …
            $table->string('employee_code', 20)->unique();

            // Personal identity
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('gender', 10)->nullable();          // male, female
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('phone2', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();

            // Identity & compliance (Ethiopian payroll requirements)
            $table->string('national_id', 50)->nullable();
            $table->string('tin_number', 30)->nullable();
            $table->string('pension_number', 30)->nullable();

            // HR status — this is the single source of truth for employment state
            $table->enum('status', ['active', 'on_leave', 'suspended', 'terminated'])
                  ->default('active');

            // Termination tracking
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            // Internal HR notes
            $table->text('hr_notes')->nullable();

            $table->timestamps();
            $table->softDeletes(); // safe archive instead of hard delete

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // ── 2. employment_details — job contract terms ───────────────────────
        // Separate from employees so contract changes are tracked over time
        Schema::create('employment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();

            // Self-referencing: who does this employee report to?
            $table->unsignedBigInteger('reporting_manager_id')->nullable();

            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])
                  ->default('full_time');

            $table->date('hire_date')->nullable();
            $table->date('contract_end_date')->nullable(); // null = permanent

            $table->string('currency', 10)->default('ETB');
            $table->decimal('salary', 12, 2)->default(0);

            $table->boolean('is_remote')->default(false);

            // Bank details for payroll
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_no', 50)->nullable();

            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')->onDelete('cascade');

            $table->foreign('department_id')
                  ->references('id')->on('departments')->onDelete('set null');

            $table->foreign('position_id')
                  ->references('id')->on('positions')->onDelete('set null');

            $table->foreign('reporting_manager_id')
                  ->references('id')->on('employees')->onDelete('set null');
        });

        // ── 3. employee_emergency_contacts ───────────────────────────────────
        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('relationship', 50)->nullable(); // Spouse, Parent, Sibling
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')->onDelete('cascade');
        });

        // ── 4. employee_qualifications ───────────────────────────────────────
        Schema::create('employee_qualifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('degree', 100);           // BSc, MA, PhD, Diploma
            $table->string('field_of_study', 150)->nullable();
            $table->string('institution', 150)->nullable();
            $table->year('graduation_year')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')->onDelete('cascade');
        });

        // ── 5. Update existing HR tables to point to employees.id ────────────
        // staff_salaries: add employee_id alongside user_id (backward compat)
        if (Schema::hasTable('staff_salaries') && !Schema::hasColumn('staff_salaries', 'employee_id')) {
            Schema::table('staff_salaries', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }

        // staff_positions: add employee_id
        if (Schema::hasTable('staff_positions') && !Schema::hasColumn('staff_positions', 'employee_id')) {
            Schema::table('staff_positions', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }

        // staff_shifts: add employee_id
        if (Schema::hasTable('staff_shifts') && !Schema::hasColumn('staff_shifts', 'employee_id')) {
            Schema::table('staff_shifts', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }

        // staff_attendances: add employee_id
        if (Schema::hasTable('staff_attendances') && !Schema::hasColumn('staff_attendances', 'employee_id')) {
            Schema::table('staff_attendances', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }

        // staff_payrolls: add employee_id
        if (Schema::hasTable('staff_payrolls') && !Schema::hasColumn('staff_payrolls', 'employee_id')) {
            Schema::table('staff_payrolls', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }

        // ── 6. Data migration: create Employee records for existing staff ────
        // staff_records is empty (0 rows confirmed), but users table has 13 staff.
        // We create an Employee record for each existing staff user.
        $staffUsers = DB::table('users')
            ->whereIn('user_type', ['admin', 'teacher', 'hr_manager', 'super_admin'])
            ->get();

        $counter = 1;
        foreach ($staffUsers as $user) {
            $nameParts = explode(' ', trim($user->name), 2);
            $firstName = $nameParts[0];
            $lastName  = $nameParts[1] ?? '';

            $employeeId = DB::table('employees')->insertGetId([
                'user_id'       => $user->id,
                'employee_code' => 'STF-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'gender'        => $user->gender ?? null,
                'date_of_birth' => $user->dob ?? null,
                'phone'         => $user->phone ?? null,
                'phone2'        => $user->phone2 ?? null,
                'email'         => $user->email ?? null,
                'address'       => $user->address ?? null,
                'photo'         => $user->photo ?? null,
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Create a default employment_details row
            DB::table('employment_details')->insert([
                'employee_id'     => $employeeId,
                'employment_type' => 'full_time',
                'currency'        => 'ETB',
                'salary'          => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $counter++;
        }

        // ── 7. Remove Step 1 columns from staff_records (they now live in employees) ──
        Schema::table('staff_records', function (Blueprint $table) {
            $columnsToDrop = [];
            $step1Columns = [
                'employment_type', 'employment_status',
                'termination_date', 'termination_reason',
                'national_id', 'tin_number', 'pension_number',
                'qualification', 'field_of_study',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
                'hr_notes',
            ];
            foreach ($step1Columns as $col) {
                if (Schema::hasColumn('staff_records', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        // Restore Step 1 columns to staff_records
        Schema::table('staff_records', function (Blueprint $table) {
            $table->enum('employment_type', ['full_time','part_time','contract','intern'])->default('full_time');
            $table->enum('employment_status', ['active','on_leave','suspended','terminated'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('tin_number', 30)->nullable();
            $table->string('pension_number', 30)->nullable();
            $table->string('qualification')->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->text('hr_notes')->nullable();
        });

        // Drop employee_id columns from HR tables
        foreach (['staff_salaries','staff_positions','staff_shifts','staff_attendances','staff_payrolls'] as $tbl) {
            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'employee_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropForeign(['employee_id']);
                    $table->dropColumn('employee_id');
                });
            }
        }

        Schema::dropIfExists('employee_qualifications');
        Schema::dropIfExists('employee_emergency_contacts');
        Schema::dropIfExists('employment_details');
        Schema::dropIfExists('employees');
    }
}
