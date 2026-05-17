<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    protected $fillable = [
        'title', 'category', 'description', 'provider',
        'duration_hours', 'cost', 'currency',
        'is_mandatory', 'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active'    => 'boolean',
        'cost'         => 'decimal:2',
    ];

    public function enrollments()
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function completedEnrollments()
    {
        return $this->hasMany(EmployeeTraining::class)->where('status', 'completed');
    }

    public function categoryLabel(): string
    {
        return match($this->category) {
            'technical'    => 'Technical',
            'pedagogical'  => 'Pedagogical',
            'leadership'   => 'Leadership',
            'compliance'   => 'Compliance',
            'certification'=> 'Certification',
            'soft_skills'  => 'Soft Skills',
            default        => 'Other',
        };
    }

    public function categoryBadgeClass(): string
    {
        return match($this->category) {
            'technical'    => 'primary',
            'pedagogical'  => 'info',
            'leadership'   => 'warning',
            'compliance'   => 'danger',
            'certification'=> 'success',
            'soft_skills'  => 'secondary',
            default        => 'light',
        };
    }
}
