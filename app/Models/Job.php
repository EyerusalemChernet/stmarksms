<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Job — Job posting for recruitment
 * 
 * Represents a job opening that the organization is recruiting for.
 * HR managers can create, edit, and manage job postings.
 */
class Job extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'department_id',
        'position_id',
        'salary_min',
        'salary_max',
        'salary_currency',
        'employment_type',
        'location',
        'requirements',
        'benefits',
        'status',
        'posted_by',
        'posted_date',
        'closing_date',
        'notes',
    ];

    protected $casts = [
        'posted_date' => 'date',
        'closing_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * The department this job belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The position this job is for
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * The employee who posted this job
     */
    public function postedBy()
    {
        return $this->belongsTo(Employee::class, 'posted_by');
    }

    /**
     * Applications for this job
     */
    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Get only active job postings
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get only open job postings (not closed)
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open')->where('closing_date', '>=', now());
    }

    /**
     * Get only closed job postings
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed')->orWhere('closing_date', '<', now());
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    /**
     * Get the number of applications for this job
     */
    public function getApplicationCountAttribute()
    {
        return $this->applications()->count();
    }

    /**
     * Get the salary range as a formatted string
     */
    public function getSalaryRangeAttribute()
    {
        if ($this->salary_min && $this->salary_max) {
            return "{$this->salary_currency} {$this->salary_min} - {$this->salary_max}";
        } elseif ($this->salary_min) {
            return "{$this->salary_currency} {$this->salary_min}+";
        } elseif ($this->salary_max) {
            return "Up to {$this->salary_currency} {$this->salary_max}";
        }
        return 'Not specified';
    }

    /**
     * Check if job is still open for applications
     */
    public function getIsOpenAttribute()
    {
        return $this->status === 'open' && $this->closing_date >= now();
    }
}
