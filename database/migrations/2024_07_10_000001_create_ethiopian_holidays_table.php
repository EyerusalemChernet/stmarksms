<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ethiopian public holidays table.
 *
 * Stores both fixed-date and calculated holidays per year.
 * HR can add/remove custom holidays (school closures, etc.).
 */
class CreateEthiopianHolidaysTable extends Migration
{
    public function up(): void
    {
        Schema::create('ethiopian_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('name', 150);
            $table->string('type', 30)->default('public');
            // public = national holiday, religious = religious observance, school = school-specific
            $table->boolean('is_paid')->default(true);
            $table->year('year')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['date', 'name']); // prevent duplicates
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethiopian_holidays');
    }
}
