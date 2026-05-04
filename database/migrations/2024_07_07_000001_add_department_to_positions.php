<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link positions to departments.
 * A position belongs to a department (nullable — some positions are cross-department).
 */
class AddDepartmentToPositions extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('name');
                $table->foreign('department_id')
                      ->references('id')->on('departments')
                      ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
}
