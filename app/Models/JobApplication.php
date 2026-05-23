<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class JobApplication extends Model
{
    protected $fillable = [
        'job_posting_id', 'first_name', 'last_name', 'email', 'phone',
        'address', 'resume_path', 'cover_letter', 'status',
        'applied_at', 'interview_date', 'reviewed_by',
    ];

    protected $casts = [
        'applied_at'     => 'date',
        'interview_date' => 'date',
    ];

    public const PIPELINE = ['applied', 'shortlisted', 'interviewed', 'hired', 'rejected'];

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function notes()
    {
        return $this->hasMany(ApplicationNote::class, 'application_id')->orderByDesc('created_at');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'applied');
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', 'shortlisted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeHired($query)
    {
        return $query->where('status', 'hired');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getResumeFileNameAttribute()
    {
        return $this->resume_path ? basename($this->resume_path) : null;
    }

    public function getResumeUrlAttribute()
    {
        return $this->resume_path ? asset('storage/' . $this->resume_path) : null;
    }

    public function getHasResumeAttribute(): bool
    {
        return $this->resume_path && Storage::disk('public')->exists($this->resume_path);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'applied'     => 'secondary',
            'shortlisted' => 'info',
            'interviewed' => 'warning',
            'hired'       => 'success',
            'rejected'    => 'danger',
            default       => 'secondary',
        };
    }

    public function isHired(): bool
    {
        return $this->status === 'hired';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function hasResume(): bool
    {
        return !is_null($this->resume_path) && Storage::disk('public')->exists($this->resume_path);
    }
}
