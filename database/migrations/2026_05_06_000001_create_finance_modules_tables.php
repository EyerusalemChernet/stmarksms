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
    }

    public function down()
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
}
