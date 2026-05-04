<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Module (mirrors laravel-hrms metrics + employee_evaluations)
 *
 * performance_categories — configurable score categories (like Metric in laravel-hrms)
 *   criteria, weight, description
 *
 * performance_reviews    — one review per employee per period
 *   employee_id, reviewer_id, period (month), overall_score, notes
 *
 * performance_scores     — one score per category per review
 *   review_id, category_id, score, weighted_score
 *
 * Formula: overall_score = sum(score × weight) / sum(weights)
 */
class CreatePerformanceTables extends Migration
{
    public function up(): void
    {
        // ── Performance Categories (Score Categories) ────────────────────────
        Schema::create('performance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Performance Reviews ──────────────────────────────────────────────
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedInteger('reviewer_id');      // user_id of HR/admin
            $table->string('period', 7);                 // Y-m e.g. "2024-07"
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period']);   // one review per employee per month

            $table->foreign('employee_id')
                  ->references('id')->on('employees')->onDelete('cascade');
        });

        // ── Performance Scores (per category per review) ─────────────────────
        Schema::create('performance_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('review_id');
            $table->unsignedBigInteger('category_id');
            $table->decimal('score', 5, 2);              // raw score (0–10)
            $table->decimal('weighted_score', 8, 4);     // score × weight
            $table->timestamps();

            $table->unique(['review_id', 'category_id']);

            $table->foreign('review_id')
                  ->references('id')->on('performance_reviews')->onDelete('cascade');
            $table->foreign('category_id')
                  ->references('id')->on('performance_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_scores');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('performance_categories');
    }
}
