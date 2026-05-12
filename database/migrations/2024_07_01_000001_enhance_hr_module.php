<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR Module Enhancement Migration
 * Adds: staff_salaries, positions, staff_positions, shifts, staff_shifts
 * Extends: staff_attendances (sign_in_time, sign_off_time, is_manually_filled)
 * Extends: staff_records (bank_acc_no, hired_on, is_remote)
 */
class EnhanceHrModule extends Migration
{
    public function up()
    {
        // ── Extend staff_records ─────────────────────────────────────────────────
        Schema::table('staff_records', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_records', 'bank_acc_no')) {
                $table->string('bank_acc_no')->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('staff_records', 'hired_on')) {
                $table->date('hired_on')->nullable()->after('bank_acc_no');
            }
            if (!Schema::hasColumn('staff_records', 'is_remote')) {
                $table->boolean('is_remote')->default(false)->after('hired_on');
            }
        });

        // ── Extend staff_attendances (add time tracking) ─────────────────────────
        Schema::table('staff_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_attendances', 'sign_in_time')) {
                $table->time('sign_in_time')->nullable()->after('status');
            }
            if (!Schema::hasColumn('staff_attendances', 'sign_off_time')) {
                $table->time('sign_off_time')->nullable()->after('sign_in_time');
            }
            if (!Schema::hasColumn('staff_attendances', 'is_manually_filled')) {
                $table->boolean('is_manually_filled')->default(true)->after('sign_off_time');
            }
        });

        // ── Staff Salaries ───────────────────────────────────────────────────────
        if (!Schema::hasTable('staff_salaries')) {
            Schema::create('staff_salaries', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('currency', 10)->default('ETB');
                $table->decimal('amount', 12, 2);
                $table->date('start_date');
                $table->date('end_date')->nullable(); // null = current salary
                $table->string('notes')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // ── Positions ────────────────────────────────────────────────────────────
        if (!Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // ── Staff Positions (history) ────────────────────────────────────────────
        if (!Schema::hasTable('staff_positions')) {
            Schema::create('staff_positions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedBigInteger('position_id');
                $table->date('start_date');
                $table->date('end_date')->nullable(); // null = current position
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
            });
        }

        // ── Shifts ───────────────────────────────────────────────────────────────
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->time('start_time');
                $table->time('end_time');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // ── Staff Shifts (history) ───────────────────────────────────────────────
        if (!Schema::hasTable('staff_shifts')) {
            Schema::create('staff_shifts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedBigInteger('shift_id');
                $table->date('start_date');
                $table->date('end_date')->nullable(); // null = current shift
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            });
        }

        // ── Staff Payrolls ───────────────────────────────────────────────────────
        if (!Schema::hasTable('staff_payrolls')) {
            Schema::create('staff_payrolls', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('currency', 10)->default('ETB');
                $table->decimal('base_salary', 12, 2)->default(0);
                $table->decimal('allowances', 12, 2)->default(0);
                $table->decimal('deductions', 12, 2)->default(0);
                $table->decimal('net_pay', 12, 2)->default(0);
                $table->string('month', 7); // e.g. 2024-07
                $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'month']);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('staff_payrolls');
        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('staff_positions');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('staff_salaries');

        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropColumn(['sign_in_time', 'sign_off_time', 'is_manually_filled']);
        });

        Schema::table('staff_records', function (Blueprint $table) {
            $table->dropColumn(['bank_acc_no', 'hired_on', 'is_remote']);
        });
    }
}
