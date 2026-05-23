<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnrollmentsTable extends Migration
{
    public function up()
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedInteger('class_id');
            $table->unsignedInteger('section_id');
            $table->string('roll_no', 20)->nullable();
            $table->enum('enrollment_status', ['active', 'superseded', 'finalized'])->default('active');
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id'], 'uq_student_year');
            $table->index('enrollment_status', 'idx_status');
            $table->index(['class_id', 'section_id', 'academic_year_id'], 'idx_class_section');

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('restrict');
            $table->foreign('class_id')->references('id')->on('my_classes')->onDelete('restrict');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('enrollments');
    }
}
