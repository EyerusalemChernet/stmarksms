<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    protected $fillable = ['category_id', 'title', 'amount', 'income_date', 'year', 'description', 'reference_no', 'created_by'];

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
