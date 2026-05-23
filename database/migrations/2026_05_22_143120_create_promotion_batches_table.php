<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionBatchesTable extends Migration
{
    public function up()
    {
        Schema::create('promotion_batches', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('from_academic_year_id');
            $table->unsignedBigInteger('to_academic_year_id');
            $table->unsignedInteger('from_class_id');
            $table->unsignedInteger('to_class_id');
            $table->enum('redistribution_mode', ['keep_same', 'random', 'balanced', 'manual'])->default('random');
            $table->enum('status', ['draft', 'finalized', 'rolled_back'])->default('draft');
            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('created_by');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_status');
            $table->index(['from_academic_year_id', 'from_class_id'], 'idx_from');

            $table->foreign('from_academic_year_id')->references('id')->on('academic_years');
            $table->foreign('to_academic_year_id')->references('id')->on('academic_years');
            $table->foreign('from_class_id')->references('id')->on('my_classes');
            $table->foreign('to_class_id')->references('id')->on('my_classes');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotion_batches');
    }
}
