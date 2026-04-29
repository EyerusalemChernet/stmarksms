<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentFeeModuleTables extends Migration
{
    public function up()
    {
        // Fee Categories (tuition, registration, exam, library, hostel, etc.)
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. Tuition, Registration
            $table->string('code')->unique(); // e.g. TUI, REG
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Fee Structures — assigned per class per session
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_category_id');
            $table->unsignedBigInteger('my_class_id');
            $table->string('session');          // e.g. 2025-2026
            $table->decimal('amount', 10, 2);
            $table->integer('installments')->default(1); // max allowed installments
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['fee_category_id', 'my_class_id', 'session']);
        });

        // Student Fee Invoices — one per student per fee structure
        Schema::create('student_fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->unsignedBigInteger('student_id');   // users.id
            $table->unsignedBigInteger('fee_structure_id');
            $table->string('session');
            $table->decimal('original_amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->decimal('fine', 10, 2)->default(0);
            $table->string('fine_reason')->nullable();
            $table->decimal('net_amount', 10, 2);       // original - discount + fine
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        // Payment Transactions — each payment against an invoice
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('collected_by');  // users.id (hr_manager)
            $table->decimal('amount', 10, 2);
            $table->integer('installment_no')->default(1);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'chapa'])->default('cash');
            $table->string('transaction_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('student_fee_invoices');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
    }
}
