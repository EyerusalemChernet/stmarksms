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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();

            // Job details
            $table->string('title', 150);
            $table->text('description');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();

            // Salary information
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('salary_currency', 3)->default('ETB');

            // Job details
            $table->string('employment_type', 50)->nullable(); // full_time, part_time, contract, temporary
            $table->string('location', 150)->nullable();
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();

            // Status and dates
            $table->enum('status', ['open', 'closed', 'archived'])->default('open');
            $table->date('posted_date');
            $table->date('closing_date')->nullable();

            // Audit
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('employees')->onDelete('set null');

            // Indexes
            $table->index('status');
            $table->index('posted_date');
            $table->index('closing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
