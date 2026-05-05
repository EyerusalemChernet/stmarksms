<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    protected $fillable = ['staff_id', 'month', 'basic_salary', 'allowances', 'deductions', 'net_salary', 'status', 'payment_method', 'notes'];

    public function staff()
    {
        return $this->belongsTo(\App\User::class, 'staff_id');
    }
}
