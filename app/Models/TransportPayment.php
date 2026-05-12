<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class TransportPayment extends Model
{
    protected $fillable = ['student_id', 'transport_id', 'session', 'month', 'amount', 'paid_at'];

    protected $dates = ['paid_at'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function transport()
    {
        return $this->belongsTo(Transport::class, 'transport_id');
    }
}
