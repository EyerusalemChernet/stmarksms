<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceModuleTables extends Migration
{
    public function up()
    {
        // Transport Fees
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('area');
            $table->decimal('fee', 10, 2)->default(0);
            $table->string('year');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('route_id');
            $table->string('year');
            $table->decimal('amt_paid', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->boolean('paid')->default(false);
            $table->timestamps();
        });

        // Payroll
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('month'); // e.g. 2026-04
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('payment_method')->default('bank_transfer');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Expenses
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('year');
            $table->text('description')->nullable();
            $table->string('receipt_no')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        // Income
        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->date('income_date');
            $table->string('year');
            $table->text('description')->nullable();
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('income_categories');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('payroll_records');
        Schema::dropIfExists('transport_records');
        Schema::dropIfExists('transport_routes');
    }
}
