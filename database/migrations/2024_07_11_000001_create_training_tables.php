<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Training & Development tables.
 *
 * training_programs   — catalog of available training courses/certifications
 * employee_trainings  — records of which employees attended which programs
 */
class CreateTrainingTables extends Migration
{
    public function up(): void
    {
        // ── Training program catalog ─────────────────────────────────────────
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->enum('category', [
                'technical',        // subject knowledge, IT skills
                'pedagogical',      // teaching methods, curriculum
                'leadership',       // management, administration
                'compliance',       // safety, legal, HR policy
                'certification',    // external certifications
                'soft_skills',      // communication, teamwork
                'other',
            ])->default('other');
            $table->text('description')->nullable();
            $table->string('provider', 150)->nullable();   // who delivers it
            $table->integer('duration_hours')->nullable(); // total hours
            $table->decimal('cost', 10, 2)->nullable();    // cost per participant
            $table->string('currency', 10)->default('ETB');
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Employee training records ────────────────────────────────────────
        Schema::create('employee_trainings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('training_program_id');

            $table->enum('status', ['enrolled', 'in_progress', 'completed', 'failed', 'cancelled'])
                  ->default('enrolled');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('completion_date')->nullable();

            // Score / result
            $table->decimal('score', 5, 2)->nullable();   // 0–100
            $table->boolean('passed')->nullable();

            // Certificate
            $table->string('certificate_number', 100)->nullable();
            $table->date('certificate_expiry')->nullable();

            // Who enrolled this employee
            $table->unsignedInteger('enrolled_by')->nullable(); // users.id

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')->onDelete('cascade');

            $table->foreign('training_program_id')
                  ->references('id')->on('training_programs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_trainings');
        Schema::dropIfExists('training_programs');
    }
}
