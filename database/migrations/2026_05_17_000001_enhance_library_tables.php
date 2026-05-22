<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Library Enhancement
 * - books: add isbn, publisher, published_year, cover_image, due_days, subject_area
 * - book_requests: add due_date, returned_at (proper timestamp), overdue_fine, notes
 */
class EnhanceLibraryTables extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'isbn'))
                $table->string('isbn', 20)->nullable()->after('name');
            if (!Schema::hasColumn('books', 'publisher'))
                $table->string('publisher', 100)->nullable()->after('author');
            if (!Schema::hasColumn('books', 'published_year'))
                $table->unsignedSmallInteger('published_year')->nullable()->after('publisher');
            if (!Schema::hasColumn('books', 'cover_image'))
                $table->string('cover_image')->nullable()->after('published_year');
            if (!Schema::hasColumn('books', 'subject_area'))
                $table->string('subject_area', 80)->nullable()->after('book_type');
            if (!Schema::hasColumn('books', 'due_days'))
                $table->unsignedTinyInteger('due_days')->default(14)->after('total_copies');
        });

        Schema::table('book_requests', function (Blueprint $table) {
            // Add columns in correct order with proper after references
            if (!Schema::hasColumn('book_requests', 'requested_at'))
                $table->timestamp('requested_at')->nullable();
            if (!Schema::hasColumn('book_requests', 'issued_at'))
                $table->timestamp('issued_at')->nullable();
            if (!Schema::hasColumn('book_requests', 'returned_at'))
                $table->timestamp('returned_at')->nullable();
            if (!Schema::hasColumn('book_requests', 'due_date'))
                $table->date('due_date')->nullable();
            if (!Schema::hasColumn('book_requests', 'overdue_fine'))
                $table->decimal('overdue_fine', 8, 2)->default(0);
            if (!Schema::hasColumn('book_requests', 'notes'))
                $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach(['isbn','publisher','published_year','cover_image','subject_area','due_days'] as $c) {
                if (Schema::hasColumn('books', $c)) $columnsToDrop[] = $c;
            }
            if (!empty($columnsToDrop)) $table->dropColumn($columnsToDrop);
        });
        Schema::table('book_requests', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach(['due_date','overdue_fine','notes','requested_at','issued_at','returned_at'] as $c) {
                if (Schema::hasColumn('book_requests', $c)) $columnsToDrop[] = $c;
            }
            if (!empty($columnsToDrop)) $table->dropColumn($columnsToDrop);
        });
    }
}
