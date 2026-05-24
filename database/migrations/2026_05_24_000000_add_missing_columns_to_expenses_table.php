<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToExpensesTable extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('expenses', 'year')) {
                $table->string('year', 9)->nullable()->after('expense_date');
            }
            if (!Schema::hasColumn('expenses', 'receipt_no')) {
                $table->string('receipt_no', 50)->nullable()->after('year');
            }
            if (!Schema::hasColumn('expenses', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('receipt_no');
            }
            if (!Schema::hasColumn('expenses', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('created_by');
            }
            if (!Schema::hasColumn('expenses', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('expenses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('expenses', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('expenses', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('expenses', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('approval_note');
            }
        });

        // Add unique constraint to receipt_no
        if (!$this->constraintExists('expenses', 'expenses_receipt_no_unique')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unique('receipt_no');
            });
        }

        // Add foreign keys
        $this->addForeignKeys();
    }

    private function addForeignKeys()
    {
        try {
            if (!$this->constraintExists('expenses', 'expenses_created_by_foreign')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                });
            }
        } catch (\Exception $e) {
            // Foreign key might already exist
        }

        try {
            if (!$this->constraintExists('expenses', 'expenses_approved_by_foreign')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                });
            }
        } catch (\Exception $e) {
            // Foreign key might already exist
        }
    }

    private function constraintExists($table, $constraintName)
    {
        try {
            $constraints = \Illuminate\Support\Facades\DB::select("
                SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = ? AND CONSTRAINT_NAME = ?
            ", [$table, $constraintName]);
            return !empty($constraints);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drop foreign keys
            if ($this->constraintExists('expenses', 'expenses_created_by_foreign')) {
                $table->dropForeignKey('expenses_created_by_foreign');
            }
            if ($this->constraintExists('expenses', 'expenses_approved_by_foreign')) {
                $table->dropForeignKey('expenses_approved_by_foreign');
            }

            // Drop unique constraint
            if ($this->constraintExists('expenses', 'expenses_receipt_no_unique')) {
                $table->dropUnique('expenses_receipt_no_unique');
            }

            // Drop columns
            $table->dropColumn([
                'year', 'receipt_no', 'created_by', 'status',
                'approved_by', 'approved_at', 'rejection_reason',
                'approval_note', 'is_locked'
            ]);
        });
    }
}

