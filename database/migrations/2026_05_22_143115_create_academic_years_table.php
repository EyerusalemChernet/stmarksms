<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The academic_years table already exists (created by the Academic Calendar feature).
 * This migration adds the is_active column needed by the Promotion module,
 * syncing it from the existing is_current column.
 */
class CreateAcademicYearsTable extends Migration
{
    public function up()
    {
        // Add is_active column if it doesn't exist (alias for is_current)
        if (!Schema::hasColumn('academic_years', 'is_active')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->tinyInteger('is_active')->default(0)->after('is_current');
            });
            // Sync is_active from is_current
            DB::statement('UPDATE academic_years SET is_active = is_current');
        }
    }

    public function down()
    {
        if (Schema::hasColumn('academic_years', 'is_active')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
}
