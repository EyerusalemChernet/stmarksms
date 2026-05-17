<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assessment Components
 *
 * Allows teachers to break the Assessment (30 marks) into named sub-components.
 * e.g.  Test = 15,  Quiz = 10,  Homework = 5  → total must equal 30.
 *
 * Scoped per: exam × class × subject
 * If no components are defined, the mark entry form falls back to a single
 * "Assessment (30)" input (existing behaviour).
 */
class CreateAssessmentComponentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();

            // Scope — which exam / class / subject this config applies to
            $table->unsignedInteger('exam_id');
            $table->unsignedInteger('my_class_id');
            $table->unsignedInteger('subject_id');

            // Component definition
            $table->string('name', 60);          // e.g. "Test", "Quiz", "Homework"
            $table->unsignedTinyInteger('max_mark'); // e.g. 15, 10, 5 — must sum to ≤ 30
            $table->unsignedTinyInteger('sort_order')->default(0);

            // Who configured this
            $table->unsignedInteger('created_by')->nullable();

            $table->timestamps();

            // One set of components per exam/class/subject
            $table->index(['exam_id', 'my_class_id', 'subject_id']);

            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_components');
    }
}
