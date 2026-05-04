<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceScore extends Model
{
    protected $fillable = ['review_id', 'category_id', 'score', 'weighted_score'];

    protected $casts = ['score' => 'float', 'weighted_score' => 'float'];

    public function review()   { return $this->belongsTo(PerformanceReview::class, 'review_id'); }
    public function category() { return $this->belongsTo(PerformanceCategory::class, 'category_id'); }
}
