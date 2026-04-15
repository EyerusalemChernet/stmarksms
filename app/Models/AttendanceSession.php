<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = ['my_class_id', 'section_id', 'teacher_id', 'date', 'year'];

    public function my_class() { return $this->belongsTo(MyClass::class); }
    public function section()  { return $this->belongsTo(Section::class); }
    public function teacher()  { return $this->belongsTo(User::class, 'teacher_id'); }
    public function records()  { return $this->hasMany(AttendanceRecord::class, 'session_id'); }
}
