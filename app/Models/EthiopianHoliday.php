<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EthiopianHoliday
 *
 * Stores Ethiopian public holidays per year.
 * Used by AttendanceService and PayrollService to exclude holidays
 * from working day calculations.
 */
class EthiopianHoliday extends Model
{
    protected $fillable = ['date', 'name', 'type', 'is_paid', 'year', 'notes'];

    protected $casts = [
        'date'    => 'date',
        'is_paid' => 'boolean',
    ];

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Type badge colour for UI.
     */
    public function typeBadgeClass(): string
    {
        return match($this->type) {
            'public'    => 'primary',
            'religious' => 'warning',
            'school'    => 'info',
            default     => 'secondary',
        };
    }
}
