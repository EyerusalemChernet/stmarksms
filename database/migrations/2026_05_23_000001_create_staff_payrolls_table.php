<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create staff_payrolls table
 * 
 * This table stores payroll records for employees including:
 * - Employee identification and period information
 * - Pay components (base salary, allowances, deductions)
 * - Tax and pension calculations
 * - Approval workflow tracking
 * - Timestamps for versioning
 */
class CreateStaffPayrollsTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_payrolls')) {
            Schema::create('staff_payrolls', function (Blueprint $table) {
                $table->id();

                // Employee reference
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->unsignedInteger('user_id')->nullable();

                // Payroll period
                $table->string('month', 7); // YYYY-MM format
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();

                // Attendance snapshot at generation time
                $table->unsignedSmallInteger('working_days')->default(0);
                $table->unsignedSmallInteger('present_days')->default(0);
                $table->unsignedSmallInteger('absent_days')->default(0);
                $table->unsignedSmallInteger('leave_days')->default(0);
                $table->decimal('overtime_hours', 6, 2)->default(0);

                // Currency and base pay
                $table->string('currency', 10)->default('ETB');
                $table->decimal('base_salary', 12, 2)->default(0);

                // Pay components
                $table->decimal('allowances', 12, 2)->default(0);
                $table->decimal('deductions', 12, 2)->default(0);

                // Ethiopian statutory deductions
                $table->decimal('income_tax', 12, 2)->default(0);
                $table->decimal('employee_pension', 12, 2)->default(0);
                $table->decimal('employer_pension', 12, 2)->default(0);

                // Net pay
                $table->decimal('net_pay', 12, 2)->default(0);

                // Workflow status
                $table->enum('status', ['draft', 'pending', 'approved', 'paid'])->default('draft');
                $table->unsignedInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('paid_at')->nullable();

                // Notes and metadata
                $table->text('notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('employee_id');
                $table->index('month');
                $table->index('status');
                $table->index(['employee_id', 'month']);

                // Foreign keys
                if (Schema::hasTable('employees')) {
                    $table->foreign('employee_id')
                          ->references('id')->on('employees')
                          ->onDelete('set null');
                }
                
                if (Schema::hasTable('users')) {
                    $table->foreign('user_id')
                          ->references('id')->on('users')
                          ->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_payrolls');
    }
}
