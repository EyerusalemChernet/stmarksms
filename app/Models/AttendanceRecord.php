<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = ['session_id', 'student_id', 'status', 'remark'];

    public function session() { return $this->belongsTo(AttendanceSession::class, 'session_id'); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
}
