<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;

class Exam extends Eloquent
{
    protected $fillable = ['name', 'term', 'year', 'start_date', 'end_date', 'description', 'status', 'created_by'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /** Auto-derive status from dates if not manually set */
    public function getComputedStatusAttribute(): string
    {
        $today = Carbon::today();

        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->status === 'completed') return 'completed';

        if ($this->start_date && $this->end_date) {
            if ($today->lt($this->start_date))  return 'upcoming';
            if ($today->gt($this->end_date))    return 'completed';
            return 'ongoing';
        }

        return $this->status ?? 'upcoming';
    }

    public function statusBadge(): array
    {
        return match($this->computed_status) {
            'ongoing'   => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Ongoing'],
            'completed' => ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => 'Completed'],
            'cancelled' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Cancelled'],
            default     => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Upcoming'],
        };
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
