<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class DiscountRequest extends Model
{
    protected $fillable = [
        'invoice_id',
        'student_id',
        'requested_by',
        'reviewed_by',
        'discount_type',
        'requested_amount',
        'approved_amount',
        'reason',
        'supporting_info',
        'status',
        'admin_note',
        'reviewed_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount'  => 'decimal:2',
        'reviewed_at'      => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(StudentFeeInvoice::class, 'invoice_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public static function typeLabel(string $type): string
    {
        return [
            'sibling'        => 'Sibling Discount',
            'employee_child' => 'Employee Child Discount',
            'scholarship'    => 'Scholarship',
            'hardship'       => 'Financial Hardship',
            'other'          => 'Other',
        ][$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
