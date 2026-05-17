<?php

namespace App\Models;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;

class Subject extends Eloquent
{
    protected $fillable = ['name', 'my_class_id', 'teacher_id', 'department_id', 'slug'];

    public function my_class()
    {
        return $this->belongsTo(MyClass::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /** Subjects a teacher can access (by department assignment or legacy teacher_id). */
    public static function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        $departmentId = StaffRecord::where('user_id', $teacherId)->value('department_id');

        return $query->where(function ($q) use ($teacherId, $departmentId) {
            $q->where('teacher_id', $teacherId);
            if ($departmentId) {
                $q->orWhere('department_id', $departmentId);
            }
        });
    }

    public function assignedLabel(): string
    {
        if ($this->department) {
            return $this->department->name;
        }

        return $this->teacher->name ?? '—';
    }

    /** User IDs of teachers responsible for subjects in the given class IDs. */
    public static function teacherUserIdsForClasses(iterable $classIds): \Illuminate\Support\Collection
    {
        $ids = collect();
        $subjects = static::whereIn('my_class_id', $classIds)->get(['teacher_id', 'department_id']);

        foreach ($subjects as $subject) {
            if ($subject->teacher_id) {
                $ids->push($subject->teacher_id);
            }
            if ($subject->department_id) {
                $deptIds = StaffRecord::where('department_id', $subject->department_id)
                    ->whereHas('user', fn ($q) => $q->where('user_type', 'teacher'))
                    ->pluck('user_id');
                $ids = $ids->merge($deptIds);
            }
        }

        return $ids->unique()->filter()->values();
    }
}
