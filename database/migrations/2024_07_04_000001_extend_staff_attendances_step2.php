<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 2 — Attendance System Enhancement
 *
 * Adds computed / tracking columns to staff_attendances:
 *   - leave_type      : granular leave classification
 *   - expected_hours  : from the employee's shift at time of marking
 *   - actual_hours    : calculated from sign_in / sign_off
 *   - overtime_hours  : actual - expected (positive = overtime)
 *   - late_minutes    : how many minutes after shift start the employee signed in
 *   - approved_by     : user_id of HR manager who approved the record
 */
class ExtendStaffAttendancesStep2 extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {

            // Granular leave type (only relevant when status = 'leave')
            if (!Schema::hasColumn('staff_attendances', 'leave_type')) {
                $table->enum('leave_type', [
                    'annual', 'sick', 'maternity', 'paternity', 'unpaid', 'other'
                ])->nullable()->after('status');
            }

            // Hours tracking
            if (!Schema::hasColumn('staff_attendances', 'expected_hours')) {
                $table->decimal('expected_hours', 5, 2)->nullable()->after('sign_off_time')
                    ->comment('Shift duration in hours at time of marking');
            }

            if (!Schema::hasColumn('staff_attendances', 'actual_hours')) {
                $table->decimal('actual_hours', 5, 2)->nullable()->after('expected_hours')
                    ->comment('Calculated from sign_in_time to sign_off_time');
            }

            if (!Schema::hasColumn('staff_attendances', 'overtime_hours')) {
                $table->decimal('overtime_hours', 5, 2)->default(0)->after('actual_hours');
            }

            if (!Schema::hasColumn('staff_attendances', 'late_minutes')) {
                $table->unsignedSmallInteger('late_minutes')->default(0)->after('overtime_hours')
                    ->comment('Minutes after shift start_time the employee signed in');
            }

            // Approval tracking
            if (!Schema::hasColumn('staff_attendances', 'approved_by')) {
                $table->unsignedInteger('approved_by')->nullable()->after('late_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'leave_type', 'expected_hours', 'actual_hours',
                'overtime_hours', 'late_minutes', 'approved_by',
            ]);
        });
    }
}
