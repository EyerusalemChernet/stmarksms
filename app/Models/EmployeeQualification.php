<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeQualification extends Model
{
    protected $fillable = [
        'employee_id',
        'degree',
        'field_of_study',
        'institution',
        'graduation_year',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
