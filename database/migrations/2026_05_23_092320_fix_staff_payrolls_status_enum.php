<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixStaffPayrollsStatusEnum extends Migration
{
    public function up()
    {
        // Add 'draft' to the ENUM — was missing, causing truncation on insert
        DB::statement(
            "ALTER TABLE staff_payrolls MODIFY COLUMN status ENUM('draft','pending','approved','paid') NOT NULL DEFAULT 'draft'"
        );
    }

    public function down()
    {
        // Revert: remove 'draft' (existing draft rows become empty string — safe for rollback only)
        DB::statement(
            "ALTER TABLE staff_payrolls MODIFY COLUMN status ENUM('pending','approved','paid') NOT NULL DEFAULT 'pending'"
        );
    }
}
