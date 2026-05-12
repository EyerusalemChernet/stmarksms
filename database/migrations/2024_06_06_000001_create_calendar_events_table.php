<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarEventsTable extends Migration
{
    public function up()
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('color', 20)->default('#4f46e5');
            $table->string('type', 50)->default('event'); // holiday, exam, meeting, event
            $table->boolean('all_day')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->string('notify_roles')->nullable(); // comma-separated: student,teacher,parent
            $table->string('google_event_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_events');
    }
}
