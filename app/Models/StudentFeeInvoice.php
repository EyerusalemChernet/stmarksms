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
        'balance', 'status', 'due_date', 'overdue_notified_at',
        'updated_by', 'admin_update_note', 'admin_updated_at',
        'chapa_ref', 'chapa_status', 'legacy_payment_record_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'overdue_notified_at' => 'datetime',
        'admin_updated_at' => 'datetime',
        'original_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'fine' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
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

    public function adminUpdater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function legacyPaymentRecord()
    {
        return $this->belongsTo(PaymentRecord::class, 'legacy_payment_record_id');
    }

    public function syncStatus()
    {
        $paid = $this->payments()->sum('amount');
        $this->amount_paid = $paid;
        $this->balance     = max(0, $this->net_amount - $paid);
        $this->status      = $this->balance <= 0 ? 'paid' : 'unpaid';
        $this->save();
    }

    public function isPayable(): bool
    {
        return $this->balance > 0 && !in_array($this->chapa_status, ['pending'], true);
    }

    public function recalculateNetAmount(): void
    {
        $this->net_amount = max(0, $this->original_amount - ($this->discount ?? 0) + ($this->fine ?? 0));
    }
}
