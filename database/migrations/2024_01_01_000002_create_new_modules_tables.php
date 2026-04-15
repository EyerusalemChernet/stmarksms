<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewModulesTables extends Migration
{
    public function up()
    {
        // Rules Engine
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // attendance_block, fee_block, promotion_requirement
            $table->string('condition'); // lt, gt, eq
            $table->decimal('value', 8, 2);
            $table->string('action'); // block_result, block_report, block_promotion
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Announcements
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('author_id');
            $table->string('title');
            $table->text('body');
            $table->string('audience')->default('all'); // all, students, teachers, parents
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sender_id');
            $table->unsignedInteger('receiver_id');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('read')->default(false);
            $table->timestamps();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('rules');
    }
}
