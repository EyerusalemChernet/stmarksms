<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminAuditToFeeSetupTables extends Migration
{
    public function up()
    {
        foreach (['fee_categories', 'fee_structures'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('admin_updated_by')->nullable();
                $table->string('admin_action', 20)->nullable();
                $table->text('admin_update_note')->nullable();
                $table->timestamp('admin_updated_at')->nullable();
            });
        }
    }

    public function down()
    {
        foreach (['fee_categories', 'fee_structures'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['admin_updated_by', 'admin_action', 'admin_update_note', 'admin_updated_at']);
            });
        }
    }
}
