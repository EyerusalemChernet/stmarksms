<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TransportRecord extends Model
{
    protected $fillable = ['student_id', 'route_id', 'year', 'amt_paid', 'balance', 'paid'];

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function student()
    {
        return $this->belongsTo(\App\User::class, 'student_id');
    }
}
