<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentComponent extends Model
{
    protected $fillable = [
        'exam_id', 'my_class_id', 'subject_id',
        'name', 'max_mark', 'sort_order', 'created_by',
    ];

    public function exam()    { return $this->belongsTo(Exam::class); }
    public function myClass() { return $this->belongsTo(MyClass::class, 'my_class_id'); }
    public function subject() { return $this->belongsTo(Subject::class); }

    /**
     * Get components for a given exam/class/subject scope.
     * Returns empty collection if none configured (→ single-input fallback).
     */
    public static function forScope(int $examId, int $classId, int $subjectId)
    {
        return static::where('exam_id', $examId)
            ->where('my_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Total max marks for a scope. Should equal 30.
     */
    public static function totalForScope(int $examId, int $classId, int $subjectId): int
    {
        return (int) static::where('exam_id', $examId)
            ->where('my_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->sum('max_mark');
    }
}
