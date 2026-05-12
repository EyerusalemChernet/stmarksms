<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceModulesTables extends Migration
{
    public function up()
    {
        // Expense Categories
        if (!Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Expenses
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('expense_category_id');
                $table->string('title', 200);
                $table->decimal('amount', 10, 2);
                $table->date('expense_date');
                $table->text('description')->nullable();
                $table->string('receipt_file')->nullable();
                $table->boolean('recurring')->default(false);
                $table->enum('recurrence_interval', ['monthly', 'quarterly', 'annually'])->nullable();
                $table->timestamps();

                $table->foreign('expense_category_id')
                    ->references('id')->on('expense_categories')->onDelete('restrict');
            });
        }

        // Salary Structures
        if (!Schema::hasTable('salary_structures')) {
            Schema::create('salary_structures', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->decimal('basic_salary', 10, 2)->default(0);
                $table->decimal('housing_allowance', 10, 2)->default(0);
                $table->decimal('transport_allowance', 10, 2)->default(0);
                $table->decimal('other_allowances', 10, 2)->default(0);
                $table->decimal('income_tax_pct', 5, 2)->default(0);
                $table->decimal('loan_repayment', 10, 2)->default(0);
                $table->decimal('absence_deduction_rate', 10, 2)->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Payrolls
        if (!Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->tinyInteger('month');
                $table->smallInteger('year');
                $table->decimal('basic_salary', 10, 2)->default(0);
                $table->decimal('housing_allowance', 10, 2)->default(0);
                $table->decimal('transport_allowance', 10, 2)->default(0);
                $table->decimal('other_allowances', 10, 2)->default(0);
                $table->decimal('bonus', 10, 2)->default(0);
                $table->decimal('gross_salary', 10, 2)->default(0);
                $table->decimal('income_tax', 10, 2)->default(0);
                $table->decimal('loan_repayment', 10, 2)->default(0);
                $table->decimal('absence_deduction', 10, 2)->default(0);
                $table->decimal('total_deductions', 10, 2)->default(0);
                $table->decimal('net_salary', 10, 2)->default(0);
                $table->tinyInteger('absence_days')->default(0);
                $table->boolean('voided')->default(false);
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
}
