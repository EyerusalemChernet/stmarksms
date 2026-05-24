<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToEmployeeUserId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Add unique constraint to prevent multiple employees from linking to same user
            // Check if the constraint already exists before adding
            if (!$this->indexExists('employees', 'employees_user_id_unique')) {
                $table->unique('user_id');
            }
        });
    }

    private function indexExists($table, $indexName)
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            "SHOW INDEXES FROM {$table} WHERE Key_name = ?",
            [$indexName]
        );
        return count($indexes) > 0;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique(['user_id']);
        });
    }
}
