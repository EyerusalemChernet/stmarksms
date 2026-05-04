<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    protected $fillable = [
        'leave_type', 'year', 'days_entitled', 'is_paid', 'carry_forward', 'description',
    ];

    protected $casts = [
        'is_paid'       => 'boolean',
        'carry_forward' => 'boolean',
    ];

    public function leaveTypeLabel(): string
    {
        return match($this->leave_type) {
            'annual'    => 'Annual Leave',
            'sick'      => 'Sick Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'unpaid'    => 'Unpaid Leave',
            'other'     => 'Other Leave',
            default     => ucfirst($this->leave_type),
        };
    }
}
