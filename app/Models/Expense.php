<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model {
    protected $fillable = [
        'expense_category_id','title','amount','expense_date',
        'description','receipt_file','recurring','recurrence_interval'
    ];
    protected $dates = ['expense_date'];

    // expense_category_id is the canonical FK; sync category_id (old column) on save
    protected static function booted()
    {
        static::saving(function ($expense) {
            if ($expense->expense_category_id && Schema::hasColumn('expenses', 'category_id')) {
                $expense->category_id = $expense->expense_category_id;
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
