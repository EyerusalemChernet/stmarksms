<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * staff_attendances.user_id was originally NOT NULL (legacy).
 * Now that the HR module uses employee_id as the primary key,
 * user_id must be nullable so employees without a system account
 * can have attendance records.
 */
class MakeStaffAttendancesUserIdNullable extends Migration
{
    public function up(): void
    {
        // Drop the unique constraint on (user_id, date) first —
        // it will be replaced by the existing unique on (employee_id, date)
        // which was added in the enhance_hr_module migration.
        // We keep user_id for backward compatibility but it is no longer
        // the primary identifier.
        Schema::table('staff_attendances', function (Blueprint $table) {
            // Drop old unique index if it still exists
            try {
                $table->dropUnique(['user_id', 'date']);
            } catch (\Exception $e) {
                // Already dropped or never existed — safe to ignore
            }

            // Make user_id nullable
            $table->unsignedInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable(false)->change();
        });
    }
}
