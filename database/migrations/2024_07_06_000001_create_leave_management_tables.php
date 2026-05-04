<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 4 — Leave Management
 *
 * Creates:
 *   leave_policies  — entitlement rules per leave type per year
 *   leave_requests  — employee leave requests with approval workflow
 *   leave_balances  — running balance per employee / type / year
 */
class CreateLeaveManagementTables extends Migration
{
    public function up(): void
    {
        // ── Leave Policies ───────────────────────────────────────────────────
        // One row per leave_type per year. Defines how many days employees
        // are entitled to. Can be overridden per employment_type.
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->enum('leave_type', [
                'annual', 'sick', 'maternity', 'paternity', 'unpaid', 'other'
            ]);
            $table->year('year');
            $table->unsignedSmallInteger('days_entitled');   // e.g. 14
            $table->boolean('is_paid')->default(true);       // unpaid = false
            $table->boolean('carry_forward')->default(false);// can unused days roll over?
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['leave_type', 'year']);
        });

        // ── Leave Requests ───────────────────────────────────────────────────
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->enum('leave_type', [
                'annual', 'sick', 'maternity', 'paternity', 'unpaid', 'other'
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days_requested');  // calculated on create
            $table->text('reason')->nullable();
            $table->string('attachment')->nullable();        // file path for sick note etc.

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                  ->default('pending');

            // Approval
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')->onDelete('cascade');
        });

        // ── Leave Balances ───────────────────────────────────────────────────
        // One row per employee / leave_type / year.
        // Updated whenever a request is approved or cancelled.
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->enum('leave_type', [
                'annual', 'sick', 'maternity', 'paternity', 'unpaid', 'other'
            ]);
            $table->year('year');
            $table->unsignedSmallInteger('entitled')->default(0);  // from policy
            $table->unsignedSmallInteger('used')->default(0);      // approved days taken
            $table->unsignedSmallInteger('pending')->default(0);   // days in pending requests
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type', 'year']);

            $table->foreign('employee_id')
                  ->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_policies');
    }
}
