<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expense_category_id', 'title', 'amount', 'expense_date',
        'description', 'receipt_file', 'recurring', 'recurrence_interval',
        'category_id', 'year', 'receipt_no', 'created_by'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'recurring' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($expense) {
            if ($expense->expense_category_id && \Illuminate\Support\Facades\Schema::hasColumn('expenses', 'category_id')) {
                $expense->category_id = $expense->expense_category_id;
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
