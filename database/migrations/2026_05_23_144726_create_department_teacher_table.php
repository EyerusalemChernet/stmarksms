<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentTeacherTable extends Migration
{
    public function up()
    {
        Schema::create('department_teacher', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id');
            $table->unsignedInteger('user_id'); // teacher user_id
            $table->primary(['department_id', 'user_id']);
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Migrate existing staff_records.department_id assignments into the pivot
        $existing = DB::table('staff_records')
            ->whereNotNull('department_id')
            ->whereHas = null; // raw query
        $rows = DB::table('staff_records')
            ->join('users', 'staff_records.user_id', '=', 'users.id')
            ->where('users.user_type', 'teacher')
            ->whereNotNull('staff_records.department_id')
            ->select('staff_records.department_id', 'staff_records.user_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('department_teacher')->insertOrIgnore([
                'department_id' => $row->department_id,
                'user_id'       => $row->user_id,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('department_teacher');
    }
}
