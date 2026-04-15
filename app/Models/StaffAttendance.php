<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    protected $fillable = ['user_id', 'date', 'status', 'remark'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
