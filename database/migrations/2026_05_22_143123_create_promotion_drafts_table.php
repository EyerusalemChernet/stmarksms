<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionDraftsTable extends Migration
{
    public function up()
    {
        Schema::create('promotion_drafts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('promotion_batch_id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('current_section_id')->nullable();
            $table->unsignedInteger('proposed_section_id')->nullable();
            $table->tinyInteger('is_locked')->default(0);
            $table->string('redistribution_group', 20)->nullable();
            $table->enum('eligibility_status', ['passed', 'held', 'conditional'])->default('passed');
            $table->decimal('yearly_average', 6, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['promotion_batch_id', 'student_id'], 'uq_batch_student');
            $table->index(['promotion_batch_id', 'proposed_section_id'], 'idx_batch_section');

            $table->foreign('promotion_batch_id')->references('id')->on('promotion_batches')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('current_section_id')->references('id')->on('sections')->onDelete('set null');
            $table->foreign('proposed_section_id')->references('id')->on('sections')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotion_drafts');
    }
}
