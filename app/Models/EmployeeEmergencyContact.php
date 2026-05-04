<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeEmergencyContact extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'phone',
        'relationship',
        'is_primary',
    ];

    protected $casts = ['is_primary' => 'boolean'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
