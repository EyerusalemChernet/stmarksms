<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * JobApplication — Job application from a candidate
 * 
 * Represents an application submitted by a candidate for a job posting.
 * Includes applicant information and attached resume/CV file.
 */
class JobApplication extends Model
{
    protected $fillable = [
        'job_posting_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'resume_path',
        'cover_letter',
        'status',
        'applied_at',
        'interview_date',
        'reviewed_by',
    ];

    protected $casts = [
        'applied_at' => 'date',
        'interview_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * The job posting this application is for
     */
    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * The employee who reviewed this application
     */
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Notes on this application
     */
    public function notes()
    {
        return $this->hasMany(ApplicationNote::class, 'application_id');
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
        return $query->where('status', 'applied');
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

    /**
     * Get hired applications
     */
    public function scopeHired($query)
    {
        return $query->where('status', 'hired');
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
            'applied' => 'warning',
            'shortlisted' => 'info',
            'interviewed' => 'primary',
            'hired' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if application is hired
     */
    public function isHired(): bool
    {
        return $this->status === 'hired';
    }

    /**
     * Check if application is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if application has resume
     */
    public function hasResume(): bool
    {
        return !is_null($this->resume_path) && \Storage::disk('public')->exists($this->resume_path);
    }
}
