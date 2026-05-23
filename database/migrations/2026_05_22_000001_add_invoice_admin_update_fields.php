<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceAdminUpdateFields extends Migration
{
    public function up()
    {
        Schema::table('student_fee_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('updated_by')->nullable()->after('due_date');
            $table->text('admin_update_note')->nullable()->after('updated_by');
            $table->timestamp('admin_updated_at')->nullable()->after('admin_update_note');
        });
    }

    public function down()
    {
        Schema::table('student_fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['updated_by', 'admin_update_note', 'admin_updated_at']);
        });
    }
}
