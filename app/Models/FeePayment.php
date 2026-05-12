<?php
namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    protected $fillable = [
        'receipt_no', 'invoice_id', 'student_id', 'collected_by',
        'amount', 'installment_no', 'payment_method',
        'transaction_ref', 'notes', 'paid_at',
    ];

    protected $dates = ['paid_at'];

    public function invoice()
    {
        return $this->belongsTo(StudentFeeInvoice::class, 'invoice_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
