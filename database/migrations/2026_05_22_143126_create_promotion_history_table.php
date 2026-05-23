<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('promotion_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('promotion_batch_id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('old_enrollment_id')->nullable();
            $table->unsignedInteger('new_enrollment_id')->nullable();
            $table->unsignedInteger('old_class_id')->nullable();
            $table->unsignedInteger('old_section_id')->nullable();
            $table->string('old_session', 20)->nullable();
            $table->enum('action_type', ['promoted', 'rolled_back']);
            $table->timestamp('action_date')->useCurrent();
            $table->unsignedInteger('performed_by');
            // No updated_at — append-only table
            $table->timestamp('created_at')->nullable();

            $table->index('promotion_batch_id', 'idx_batch');
            $table->index('student_id', 'idx_student');

            $table->foreign('promotion_batch_id')->references('id')->on('promotion_batches');
            $table->foreign('student_id')->references('id')->on('users');
            $table->foreign('performed_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotion_history');
    }
}
