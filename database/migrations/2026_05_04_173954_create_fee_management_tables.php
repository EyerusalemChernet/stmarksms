<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeeManagementTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('fee_categories')) {
            Schema::create('fee_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 10)->unique();
                $table->text('description')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fee_structures')) {
            Schema::create('fee_structures', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fee_category_id');
                $table->unsignedInteger('my_class_id');
                $table->string('session');
                $table->decimal('amount', 10, 2);
                $table->integer('installments')->default(1);
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->foreign('fee_category_id')->references('id')->on('fee_categories')->onDelete('cascade');
                $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('student_fee_invoices')) {
            Schema::create('student_fee_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_no')->unique();
                $table->unsignedInteger('student_id');
                $table->unsignedBigInteger('fee_structure_id');
                $table->string('session');
                $table->decimal('original_amount', 10, 2);
                $table->decimal('discount', 10, 2)->default(0);
                $table->string('discount_reason')->nullable();
                $table->decimal('fine', 10, 2)->default(0);
                $table->string('fine_reason')->nullable();
                $table->decimal('net_amount', 10, 2);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->decimal('balance', 10, 2);
                $table->string('status')->default('unpaid'); // unpaid, partial, paid
                $table->date('due_date')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('fee_structure_id')->references('id')->on('fee_structures')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('fee_payments')) {
            Schema::create('fee_payments', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_no')->unique();
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedInteger('student_id');
                $table->unsignedInteger('collected_by');
                $table->decimal('amount', 10, 2);
                $table->integer('installment_no')->nullable();
                $table->string('payment_method')->default('cash');
                $table->string('transaction_ref')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->foreign('invoice_id')->references('id')->on('student_fee_invoices')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('collected_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('student_fee_invoices');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
    }
}
