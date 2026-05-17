<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceTimetableForAutoGenerator extends Migration
{
    public function up()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->unsignedInteger('section_id')->nullable()->after('my_class_id');
        });

        Schema::table('time_slots', function (Blueprint $table) {
            $table->string('slot_type', 20)->default('period')->after('ttr_id');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('slot_type');
            $table->string('label', 100)->nullable()->after('sort_order');
        });

        Schema::table('time_tables', function (Blueprint $table) {
            $table->unsignedInteger('teacher_id')->nullable()->after('subject_id');
        });
    }

    public function down()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->dropColumn('section_id');
        });

        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropColumn(['slot_type', 'sort_order', 'label']);
        });

        Schema::table('time_tables', function (Blueprint $table) {
            $table->dropColumn('teacher_id');
        });
    }
}
