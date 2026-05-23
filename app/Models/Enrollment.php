<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $table = 'enrollments';

    protected $fillable = [
        'student_id', 'academic_year_id', 'class_id',
        'section_id', 'roll_no', 'enrollment_status',
    ];

    // ── Immutability guard ───────────────────────────────────────────────────

    public function save(array $options = [])
    {
        if ($this->exists && in_array($this->getOriginal('enrollment_status'), ['superseded', 'finalized'])) {
            throw new \RuntimeException(
                "Enrollment #{$this->id} is {$this->getOriginal('enrollment_status')} and cannot be modified."
            );
        }
        return parent::save($options);
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function student()      { return $this->belongsTo(User::class, 'student_id'); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class, 'academic_year_id'); }
    public function myClass()      { return $this->belongsTo(MyClass::class, 'class_id'); }
    public function section()      { return $this->belongsTo(Section::class, 'section_id'); }

    // ── Query scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('enrollment_status', 'active');
    }

    public function scopeForYear($query, int $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }
}
