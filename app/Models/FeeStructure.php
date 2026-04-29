<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $fillable = ['fee_category_id', 'my_class_id', 'session', 'amount', 'installments', 'active'];

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
}
