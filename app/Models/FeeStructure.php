<?php
namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $fillable = [
        'fee_category_id', 'my_class_id', 'session', 'amount', 'installments', 'active',
        'admin_updated_by', 'admin_action', 'admin_update_note', 'admin_updated_at',
    ];

    protected $casts = [
        'admin_updated_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function my_class()
    {
        return $this->belongsTo(MyClass::class, 'my_class_id');
    }

    public function invoices()
    {
        return $this->hasMany(StudentFeeInvoice::class, 'fee_structure_id');
    }

    public function adminUpdater()
    {
        return $this->belongsTo(User::class, 'admin_updated_by');
    }
}
