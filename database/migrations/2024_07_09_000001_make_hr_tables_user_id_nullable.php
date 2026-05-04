<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make user_id nullable on all HR history tables.
 * These tables now use employee_id as the primary FK.
 * user_id is kept for backward compatibility only.
 */
class MakeHrTablesUserIdNullable extends Migration
{
    public function up(): void
    {
        $tables = ['staff_shifts', 'staff_salaries', 'staff_positions', 'staff_payrolls'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedInteger('user_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally left empty — reverting to NOT NULL would break existing data
    }
}
