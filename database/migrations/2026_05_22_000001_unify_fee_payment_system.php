<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UnifyFeePaymentSystem extends Migration
{
    public function up()
    {
        Schema::table('student_fee_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fee_invoices', 'chapa_ref')) {
                $table->string('chapa_ref')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('student_fee_invoices', 'chapa_status')) {
                $table->string('chapa_status')->nullable()->after('chapa_ref');
            }
            if (!Schema::hasColumn('student_fee_invoices', 'legacy_payment_record_id')) {
                $table->unsignedBigInteger('legacy_payment_record_id')->nullable()->after('chapa_status');
                $table->unique('legacy_payment_record_id');
            }
        });

        Schema::table('payment_records', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_records', 'migrated_to_invoice_id')) {
                $table->unsignedBigInteger('migrated_to_invoice_id')->nullable()->after('chapa_status');
            }
        });
    }

    public function down()
    {
        Schema::table('student_fee_invoices', function (Blueprint $table) {
            $cols = ['chapa_ref', 'chapa_status', 'legacy_payment_record_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('student_fee_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('payment_records', function (Blueprint $table) {
            if (Schema::hasColumn('payment_records', 'migrated_to_invoice_id')) {
                $table->dropColumn('migrated_to_invoice_id');
            }
        });
    }
}
