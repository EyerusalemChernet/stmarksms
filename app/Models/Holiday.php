<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'academic_year_id', 'name', 'date', 'source', 'country_code', 'is_school_day',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_school_day'=> 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
