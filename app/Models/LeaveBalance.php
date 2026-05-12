<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type', 'year', 'entitled', 'used', 'pending',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // ── Computed ─────────────────────────────────────────────────────────────

    /** Days still available to take (not counting pending) */
    public function getAvailableAttribute(): int
    {
        return max(0, $this->entitled - $this->used - $this->pending);
    }

    /** Days available including pending (optimistic view) */
    public function getAvailableOptimisticAttribute(): int
    {
        return max(0, $this->entitled - $this->used);
    }
}
