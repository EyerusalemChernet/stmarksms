<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'description'];

    public function staffShifts()
    {
        return $this->hasMany(StaffShift::class);
    }

    /** Staff currently on this shift */
    public function currentStaff()
    {
        return $this->hasMany(StaffShift::class)->whereNull('end_date')->with('user');
    }

    /** Duration in hours (handles overnight shifts) */
    public function durationHours(): float
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end   = \Carbon\Carbon::parse($this->end_time);
        if ($end->lessThan($start)) {
            $end->addDay();
        }
        return round($start->diffInMinutes($end) / 60, 2);
    }
}
