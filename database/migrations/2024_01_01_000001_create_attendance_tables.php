<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTables extends Migration
{
    public function up()
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('my_class_id');
            $table->unsignedInteger('section_id');
            $table->unsignedInteger('teacher_id');
            $table->date('date');
            $table->string('year');
            $table->timestamps();
            $table->unique(['my_class_id', 'section_id', 'date']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedInteger('student_id');
            $table->enum('status', ['present', 'absent', 'late'])->default('absent');
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'student_id']);
            $table->foreign('session_id')->references('id')->on('attendance_sessions')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
    }
}
