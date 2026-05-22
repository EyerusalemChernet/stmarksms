<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expense_category_id', 'title', 'amount', 'expense_date',
        'description', 'receipt_file', 'recurring', 'recurrence_interval',
        'category_id', 'year', 'receipt_no', 'created_by',
        'status', 'approved_by', 'approved_at', 'rejection_reason', 'approval_note', 'is_locked',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'recurring'    => 'boolean',
        'is_locked'    => 'boolean',
        'approved_at'  => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($expense) {
            if ($expense->expense_category_id && \Illuminate\Support\Facades\Schema::hasColumn('expenses', 'category_id')) {
                $expense->category_id = $expense->expense_category_id;
            }
            if (empty($expense->year)) {
                $expense->year = \App\Helpers\Qs::financeYearForDate($expense->expense_date);
            }
        });
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
