<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recruitment Module
 *
 * job_postings   — open positions advertised by HR
 * job_applications — candidates who applied
 * application_notes — internal HR notes / status history per application
 */
class CreateRecruitmentTables extends Migration
{
    public function up(): void
    {
        // ── Job Postings ─────────────────────────────────────────────────────
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])
                  ->default('full_time');
            $table->unsignedSmallInteger('vacancies')->default(1);
            $table->date('deadline')->nullable();
            $table->enum('status', ['open', 'closed', 'on_hold'])->default('open');
            $table->unsignedInteger('created_by')->nullable(); // user_id
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('set null');
        });

        // ── Job Applications ─────────────────────────────────────────────────
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_posting_id');
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('resume_path')->nullable();   // uploaded CV file
            $table->text('cover_letter')->nullable();

            // Pipeline: applied → shortlisted → interviewed → hired / rejected
            $table->enum('status', ['applied', 'shortlisted', 'interviewed', 'hired', 'rejected'])
                  ->default('applied');

            $table->date('applied_at');
            $table->date('interview_date')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();  // user_id of HR reviewer
            $table->timestamps();

            $table->foreign('job_posting_id')
                  ->references('id')->on('job_postings')->onDelete('cascade');
        });

        // ── Application Notes (status history + HR comments) ─────────────────
        Schema::create('application_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedInteger('user_id');          // who wrote the note
            $table->string('status_changed_to')->nullable(); // null = just a note
            $table->text('note');
            $table->timestamps();

            $table->foreign('application_id')
                  ->references('id')->on('job_applications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_notes');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
    }
}
