<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $fillable = ['route_name', 'vehicle_no', 'driver_name', 'driver_phone', 'fee', 'active'];

    public function payments()
    {
        return $this->hasMany(TransportPayment::class, 'transport_id');
    }
}
