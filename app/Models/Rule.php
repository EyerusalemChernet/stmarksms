<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $fillable = ['name', 'type', 'condition', 'value', 'action', 'active', 'description'];
    protected $casts = ['active' => 'boolean', 'value' => 'float'];
}
