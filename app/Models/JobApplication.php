<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * JobApplication — Job application from a candidate
 * 
 * Represents an application submitted by a candidate for a job posting.
 * Includes applicant information and attached resume/CV.
 */
class JobApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'job_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'resume_path',
        'cover_letter',
        'status',
        'applied_date',
        'reviewed_by',
        'reviewed_date',
        'notes',
    ];

    protected $casts = [
        'applied_date' => 'datetime',
        'reviewed_date' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * The job this application is for
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * The employee who reviewed this application
     */
    public function reviewedBy()
    {
        return $this->belongsTo(Employee::class, 'reviewed_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Get applications with a specific status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get pending applications (not yet reviewed)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get shortlisted applications
     */
    public function scopeShortlisted($query)
    {
        return $query->where('status', 'shortlisted');
    }

    /**
     * Get rejected applications
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    /**
     * Get the full name of the applicant
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the resume file name
     */
    public function getResumeFileNameAttribute()
    {
        if ($this->resume_path) {
            return basename($this->resume_path);
        }
        return null;
    }

    /**
     * Get the resume download URL
     */
    public function getResumeUrlAttribute()
    {
        if ($this->resume_path) {
            return asset('storage/' . $this->resume_path);
        }
        return null;
    }

    /**
     * Check if resume file exists
     */
    public function getHasResumeAttribute()
    {
        if ($this->resume_path) {
            return \Storage::disk('public')->exists($this->resume_path);
        }
        return false;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'shortlisted' => 'success',
            'rejected' => 'danger',
            'hired' => 'info',
            default => 'secondary',
        };
    }
}
