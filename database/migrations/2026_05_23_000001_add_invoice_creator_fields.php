<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceCreatorFields extends Migration
{
    public function up()
    {
        Schema::table('student_fee_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('due_date');
            $table->timestamp('admin_created_at')->nullable()->after('created_by');
        });
    }

    public function down()
    {
        Schema::table('student_fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'admin_created_at']);
        });
    }
}
