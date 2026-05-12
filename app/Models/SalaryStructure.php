<?php
namespace App\Models;
use App\User;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model {
    protected $fillable = [
        'user_id','basic_salary','housing_allowance','transport_allowance',
        'other_allowances','income_tax_pct','loan_repayment','absence_deduction_rate','active'
    ];
    public function staff() { return $this->belongsTo(User::class, 'user_id'); }
}
