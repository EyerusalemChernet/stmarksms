<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDepartmentIdToSubjectsTable extends Migration
{
    public function up()
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('my_class_id');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });

        DB::statement('ALTER TABLE subjects MODIFY teacher_id INT UNSIGNED NULL');

        foreach (DB::table('subjects')->whereNotNull('teacher_id')->get() as $subject) {
            $departmentId = DB::table('staff_records')
                ->where('user_id', $subject->teacher_id)
                ->whereNotNull('department_id')
                ->value('department_id');

            if ($departmentId) {
                DB::table('subjects')->where('id', $subject->id)->update(['department_id' => $departmentId]);
            }
        }
    }

    public function down()
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        DB::statement('ALTER TABLE subjects MODIFY teacher_id INT UNSIGNED NOT NULL');

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('users');
        });
    }
}
