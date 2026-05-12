<?php
namespace App\Models;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model {
    protected $fillable = [
        'user_id','month','year',
        'basic_salary','housing_allowance','transport_allowance','other_allowances','bonus',
        'gross_salary','income_tax','loan_repayment','absence_deduction',
        'total_deductions','net_salary','absence_days','voided','processed_at'
    ];
    protected $dates = ['processed_at'];
    public function staff() { return $this->belongsTo(User::class, 'user_id'); }
}
