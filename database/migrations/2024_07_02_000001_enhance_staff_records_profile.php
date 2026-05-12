<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 1 — Employee Profile Enhancement
 *
 * Adds production-grade HR profile fields to staff_records.
 * All new columns are nullable so existing rows are unaffected.
 */
class EnhanceStaffRecordsProfile extends Migration
{
    public function up(): void
    {
        Schema::table('staff_records', function (Blueprint $table) {

            // ── Employment classification ────────────────────────────────────
            if (!Schema::hasColumn('staff_records', 'employment_type')) {
                $table->enum('employment_type', [
                    'full_time', 'part_time', 'contract', 'intern'
                ])->default('full_time')->after('is_remote');
            }

            if (!Schema::hasColumn('staff_records', 'employment_status')) {
                $table->enum('employment_status', [
                    'active', 'on_leave', 'suspended', 'terminated'
                ])->default('active')->after('employment_type');
            }

            // ── Termination (filled only when status = terminated) ───────────
            if (!Schema::hasColumn('staff_records', 'termination_date')) {
                $table->date('termination_date')->nullable()->after('employment_status');
            }

            if (!Schema::hasColumn('staff_records', 'termination_reason')) {
                $table->text('termination_reason')->nullable()->after('termination_date');
            }

            // ── Identity & compliance (Ethiopian payroll requirements) ────────
            if (!Schema::hasColumn('staff_records', 'national_id')) {
                $table->string('national_id', 50)->nullable()->after('termination_reason');
            }

            if (!Schema::hasColumn('staff_records', 'tin_number')) {
                $table->string('tin_number', 30)->nullable()->after('national_id');
            }

            if (!Schema::hasColumn('staff_records', 'pension_number')) {
                $table->string('pension_number', 30)->nullable()->after('tin_number');
            }

            // ── Qualification ────────────────────────────────────────────────
            if (!Schema::hasColumn('staff_records', 'qualification')) {
                $table->string('qualification')->nullable()->after('pension_number')
                    ->comment('e.g. BSc Education, MA TESOL');
            }

            if (!Schema::hasColumn('staff_records', 'field_of_study')) {
                $table->string('field_of_study')->nullable()->after('qualification');
            }

            // ── Emergency contact ────────────────────────────────────────────
            if (!Schema::hasColumn('staff_records', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('field_of_study');
            }

            if (!Schema::hasColumn('staff_records', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            }

            if (!Schema::hasColumn('staff_records', 'emergency_contact_relation')) {
                $table->string('emergency_contact_relation', 50)->nullable()->after('emergency_contact_phone')
                    ->comment('e.g. Spouse, Parent, Sibling');
            }

            // ── Notes (HR internal) ──────────────────────────────────────────
            if (!Schema::hasColumn('staff_records', 'hr_notes')) {
                $table->text('hr_notes')->nullable()->after('emergency_contact_relation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_records', function (Blueprint $table) {
            $table->dropColumn([
                'employment_type',
                'employment_status',
                'termination_date',
                'termination_reason',
                'national_id',
                'tin_number',
                'pension_number',
                'qualification',
                'field_of_study',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relation',
                'hr_notes',
            ]);
        });
    }
}
