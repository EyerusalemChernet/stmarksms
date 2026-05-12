<?php
namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StudentFeeInvoice extends Model
{
    protected $fillable = [
        'invoice_no', 'student_id', 'fee_structure_id', 'session',
        'original_amount', 'discount', 'discount_reason',
        'fine', 'fine_reason', 'net_amount', 'amount_paid',
        'balance', 'status', 'due_date',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function fee_structure()
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function payments()
    {
        return $this->hasMany(FeePayment::class, 'invoice_id');
    }

    // Recalculate and sync balance/status after a payment
    public function syncStatus()
    {
        $paid = $this->payments()->sum('amount');
        $this->amount_paid = $paid;
        $this->balance     = max(0, $this->net_amount - $paid);
        $this->status      = $paid <= 0 ? 'unpaid' : ($this->balance <= 0 ? 'paid' : 'partial');
        $this->save();
    }
}
