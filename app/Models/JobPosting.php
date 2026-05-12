<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    protected $fillable = [
        'title', 'department_id', 'position_id', 'description', 'requirements',
        'employment_type', 'vacancies', 'deadline', 'status', 'created_by',
    ];

    protected $casts = ['deadline' => 'date'];

    public function department()  { return $this->belongsTo(Department::class); }
    public function position()    { return $this->belongsTo(Position::class); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by'); }
    public function applications(){ return $this->hasMany(JobApplication::class); }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'open'    => 'success',
            'closed'  => 'secondary',
            'on_hold' => 'warning',
            default   => 'secondary',
        };
    }

    public function isOpen(): bool { return $this->status === 'open'; }
}
