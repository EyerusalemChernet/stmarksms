<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            // Job reference
            $table->unsignedBigInteger('job_id');

            // Applicant information
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 100);
            $table->string('phone', 20);

            // Resume/CV file
            $table->string('resume_path')->nullable(); // Path to uploaded resume
            $table->text('cover_letter')->nullable();

            // Application status
            $table->enum('status', ['pending', 'shortlisted', 'rejected', 'hired'])->default('pending');
            $table->dateTime('applied_date');

            // Review information
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('employees')->onDelete('set null');

            // Indexes
            $table->index('job_id');
            $table->index('status');
            $table->index('applied_date');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
