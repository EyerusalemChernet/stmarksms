<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeTraining extends Model
{
    protected $fillable = [
        'employee_id', 'training_program_id', 'status',
        'start_date', 'end_date', 'completion_date',
        'score', 'passed', 'certificate_number', 'certificate_expiry',
        'enrolled_by', 'notes',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'completion_date'   => 'date',
        'certificate_expiry'=> 'date',
        'passed'            => 'boolean',
        'score'             => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function program()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function enrolledBy()
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isExpired(): bool
    {
        return $this->certificate_expiry && $this->certificate_expiry->isPast();
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'enrolled'    => 'secondary',
            'in_progress' => 'info',
            'completed'   => 'success',
            'failed'      => 'danger',
            'cancelled'   => 'dark',
            default       => 'secondary',
        };
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'enrolled'    => 'Enrolled',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'failed'      => 'Failed',
            'cancelled'   => 'Cancelled',
            default       => ucfirst($this->status),
        };
    }
}
