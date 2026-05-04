<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarConflict extends Model
{
    protected $fillable = [
        'academic_year_id', 'calendar_event_id',
        'conflict_type', 'original_date', 'resolved_date',
        'resolution', 'ai_suggestion',
    ];

    protected $casts = [
        'original_date' => 'date',
        'resolved_date' => 'date',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function event()
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }
}
