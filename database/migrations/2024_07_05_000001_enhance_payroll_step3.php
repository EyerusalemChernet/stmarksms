<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 3 — Payroll System Enhancement
 *
 * 1. Extends staff_payrolls with period tracking, attendance stats,
 *    itemised tax/pension fields, and approval workflow.
 *
 * 2. Creates payroll_items — line-item breakdown of each payroll
 *    (replaces the single allowances/deductions lump sums).
 *
 * 3. Makes staff_payrolls.user_id nullable (same reason as attendance).
 */
class EnhancePayrollStep3 extends Migration
{
    public function up(): void
    {
        // ── Extend staff_payrolls ────────────────────────────────────────────
        Schema::table('staff_payrolls', function (Blueprint $table) {

            // Make user_id nullable (legacy column)
            $table->unsignedInteger('user_id')->nullable()->change();

            // Pay period (more precise than just month)
            if (!Schema::hasColumn('staff_payrolls', 'period_start')) {
                $table->date('period_start')->nullable()->after('month');
            }
            if (!Schema::hasColumn('staff_payrolls', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }

            // Attendance stats snapshot at generation time
            if (!Schema::hasColumn('staff_payrolls', 'working_days')) {
                $table->unsignedSmallInteger('working_days')->default(0)->after('period_end');
            }
            if (!Schema::hasColumn('staff_payrolls', 'present_days')) {
                $table->unsignedSmallInteger('present_days')->default(0)->after('working_days');
            }
            if (!Schema::hasColumn('staff_payrolls', 'absent_days')) {
                $table->unsignedSmallInteger('absent_days')->default(0)->after('present_days');
            }
            if (!Schema::hasColumn('staff_payrolls', 'leave_days')) {
                $table->unsignedSmallInteger('leave_days')->default(0)->after('absent_days');
            }
            if (!Schema::hasColumn('staff_payrolls', 'overtime_hours')) {
                $table->decimal('overtime_hours', 6, 2)->default(0)->after('leave_days');
            }

            // Ethiopian statutory deductions (stored separately for transparency)
            if (!Schema::hasColumn('staff_payrolls', 'income_tax')) {
                $table->decimal('income_tax', 10, 2)->default(0)->after('deductions');
            }
            if (!Schema::hasColumn('staff_payrolls', 'employee_pension')) {
                $table->decimal('employee_pension', 10, 2)->default(0)->after('income_tax');
            }
            if (!Schema::hasColumn('staff_payrolls', 'employer_pension')) {
                $table->decimal('employer_pension', 10, 2)->default(0)->after('employee_pension');
            }

            // Approval workflow
            if (!Schema::hasColumn('staff_payrolls', 'approved_by')) {
                $table->unsignedInteger('approved_by')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('staff_payrolls', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('staff_payrolls', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('approved_at');
            }
        });

        // ── payroll_items — line-item breakdown ──────────────────────────────
        if (!Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_id');
                $table->enum('type', ['earning', 'deduction']);
                $table->string('label', 100);          // e.g. "Transport Allowance", "Income Tax"
                $table->decimal('amount', 10, 2);
                $table->string('note', 255)->nullable();
                $table->timestamps();

                $table->foreign('payroll_id')
                      ->references('id')->on('staff_payrolls')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');

        Schema::table('staff_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'period_start', 'period_end',
                'working_days', 'present_days', 'absent_days', 'leave_days', 'overtime_hours',
                'income_tax', 'employee_pension', 'employer_pension',
                'approved_by', 'approved_at', 'paid_at',
            ]);
        });
    }
}
