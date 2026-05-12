<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarRule extends Model
{
    protected $fillable = [
        'name', 'rule_type', 'event_type', 'rule_value',
        'color', 'notify_email', 'notify_roles',
        'is_active', 'sort_order', 'description',
    ];

    protected $casts = [
        'rule_value'   => 'array',
        'notify_email' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }
}
