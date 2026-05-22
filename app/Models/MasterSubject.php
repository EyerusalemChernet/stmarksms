<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSubject extends Model
{
    protected $table = 'master_subjects';

    protected $fillable = ['name', 'code', 'description'];

    /** All class-subject assignments that use this master */
    public function classSubjects()
    {
        return $this->hasMany(Subject::class, 'master_subject_id');
    }

    /** Classes this subject is assigned to */
    public function classes()
    {
        return $this->belongsToMany(MyClass::class, 'subjects', 'master_subject_id', 'my_class_id')
                    ->withPivot(['id', 'department_id', 'teacher_id'])
                    ->withTimestamps();
    }
}
