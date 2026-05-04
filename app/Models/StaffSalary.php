<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StaffSalary extends Model
{
    protected $fillable = [
        'employee_id', // new — HR module uses this
        'user_id',     // legacy — kept for backward compatibility
        'currency',
        'amount',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    /** New relationship — HR module */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** Legacy relationship — school system */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
