<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterExpensesTable extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'expense_category_id')) {
                // Add alias column pointing to same FK as category_id
                $table->unsignedBigInteger('expense_category_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('expenses', 'receipt_file')) {
                $table->string('receipt_file')->nullable()->after('description');
            }
            if (!Schema::hasColumn('expenses', 'recurring')) {
                $table->boolean('recurring')->default(false)->after('receipt_file');
            }
            if (!Schema::hasColumn('expenses', 'recurrence_interval')) {
                $table->string('recurrence_interval')->nullable()->after('recurring');
            }
            if (!Schema::hasColumn('expenses', 'expense_date')) {
                $table->date('expense_date')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('expenses', 'title')) {
                $table->string('title', 200)->nullable()->after('expense_category_id');
            }
        });
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['expense_category_id', 'receipt_file', 'recurring', 'recurrence_interval']);
        });
    }
}
