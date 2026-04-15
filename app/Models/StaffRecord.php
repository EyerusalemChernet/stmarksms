<?php

namespace App\Models;

use App\User;
use App\Models\Department;
use Eloquent;

class StaffRecord extends Eloquent
{
    protected $fillable = ['code', 'emp_date', 'user_id', 'department_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
