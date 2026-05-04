<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PerformanceCategory — mirrors laravel-hrms Metric model.
 * Configurable score categories with weights.
 */
class PerformanceCategory extends Model
{
    protected $fillable = ['name', 'weight', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'weight' => 'float'];

    public function scores()
    {
        return $this->hasMany(PerformanceScore::class, 'category_id');
    }
}
