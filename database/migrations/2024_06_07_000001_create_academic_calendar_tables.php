<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademicCalendarTables extends Migration
{
    public function up()
    {
        // ── 1. Academic Years ─────────────────────────────────────────────────
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);           // e.g. "2016/17 E.C." or "2024/25"
            $table->string('eth_name', 20)->nullable(); // Ethiopian year label
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft','active','archived'])->default('draft');
            $table->boolean('is_current')->default(false);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // ── 2. Calendar Rules ─────────────────────────────────────────────────
        Schema::create('calendar_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');                // "First Day of School"
            $table->string('rule_type', 50);       // fixed_date|week_offset|nth_weekday|semester_relative
            $table->string('event_type', 50)->default('event'); // holiday|exam|break|meeting|event
            $table->json('rule_value');            // flexible JSON payload per rule_type
            $table->string('color', 20)->default('#4f46e5');
            $table->boolean('notify_email')->default(false);
            $table->string('notify_roles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ── 3. Holidays ───────────────────────────────────────────────────────
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id');
            $table->string('name');
            $table->date('date');
            $table->string('source', 30)->default('manual'); // manual|api|generated
            $table->string('country_code', 5)->default('ET');
            $table->boolean('is_school_day')->default(false);
            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
            $table->unique(['academic_year_id', 'date', 'name']);
        });

        // ── 4. Generated Events (linked to academic year) ─────────────────────
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->nullable()->after('id');
            $table->unsignedBigInteger('calendar_rule_id')->nullable()->after('academic_year_id');
            $table->boolean('auto_generated')->default(false)->after('calendar_rule_id');
            $table->boolean('conflict_resolved')->default(false)->after('auto_generated');
            $table->string('conflict_note')->nullable()->after('conflict_resolved');
        });

        // ── 5. Conflict Log ───────────────────────────────────────────────────
        Schema::create('calendar_conflicts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('calendar_event_id')->nullable();
            $table->string('conflict_type', 50); // holiday_overlap|weekend|break_overlap
            $table->date('original_date');
            $table->date('resolved_date')->nullable();
            $table->string('resolution', 100)->nullable();
            $table->text('ai_suggestion')->nullable();
            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn(['academic_year_id','calendar_rule_id','auto_generated','conflict_resolved','conflict_note']);
        });
        Schema::dropIfExists('calendar_conflicts');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('calendar_rules');
        Schema::dropIfExists('academic_years');
    }
}
