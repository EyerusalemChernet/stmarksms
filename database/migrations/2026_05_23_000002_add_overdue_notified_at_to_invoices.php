<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fee_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fee_invoices', 'overdue_notified_at')) {
                $table->timestamp('overdue_notified_at')->nullable()->after('due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('student_fee_invoices', 'overdue_notified_at')) {
                $table->dropColumn('overdue_notified_at');
            }
        });
    }
};
