<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    protected $fillable = ['name', 'area', 'fee', 'year', 'description'];

    public function records()
    {
        return $this->hasMany(TransportRecord::class, 'route_id');
    }
}
