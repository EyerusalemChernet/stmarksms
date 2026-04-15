<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionErpTables extends Migration
{
    public function up()
    {
        // ── Audit Logs ──────────────────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('action');          // created, updated, deleted, etc.
            $table->string('module');          // students, attendance, marks, payments, library
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // ── Departments (HR) ────────────────────────────────────────────────────
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ── Staff Attendance ────────────────────────────────────────────────────
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'leave'])->default('present');
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        // ── Add department_id to staff_records ──────────────────────────────────
        if (!Schema::hasColumn('staff_records', 'department_id')) {
            Schema::table('staff_records', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('emp_date');
            });
        }

        // ── Add payment_method to receipts ──────────────────────────────────────
        if (!Schema::hasColumn('receipts', 'payment_method')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->string('payment_method')->default('cash')->after('balance');
                $table->string('transaction_ref')->nullable()->after('payment_method');
                $table->string('payment_status')->default('completed')->after('transaction_ref');
            });
        }

        // ── Add chapa_ref to payment_records ────────────────────────────────────
        if (!Schema::hasColumn('payment_records', 'chapa_ref')) {
            Schema::table('payment_records', function (Blueprint $table) {
                $table->string('chapa_ref')->nullable()->after('paid');
                $table->string('chapa_status')->nullable()->after('chapa_ref');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('audit_logs');

        Schema::table('staff_records', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'transaction_ref', 'payment_status']);
        });
        Schema::table('payment_records', function (Blueprint $table) {
            $table->dropColumn(['chapa_ref', 'chapa_status']);
        });
    }
}
