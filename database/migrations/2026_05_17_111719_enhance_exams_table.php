<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhance exams table:
 * - start_date / end_date  — when the exam period runs
 * - description            — optional notes for teachers
 * - status                 — upcoming | ongoing | completed | cancelled
 * - created_by             — which admin created it
 */
class EnhanceExamsTable extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'start_date'))
                $table->date('start_date')->nullable()->after('year');
            if (!Schema::hasColumn('exams', 'end_date'))
                $table->date('end_date')->nullable()->after('start_date');
            if (!Schema::hasColumn('exams', 'description'))
                $table->text('description')->nullable()->after('end_date');
            if (!Schema::hasColumn('exams', 'status'))
                $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])
                      ->default('upcoming')->after('description');
            if (!Schema::hasColumn('exams', 'created_by'))
                $table->unsignedInteger('created_by')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $cols = ['start_date','end_date','description','status','created_by'];
            $table->dropColumn(array_filter($cols, fn($c) => Schema::hasColumn('exams', $c)));
        });
    }
}
