<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    protected $fillable = ['employee_id', 'reviewer_id', 'period', 'overall_score', 'notes'];

    protected $casts = ['overall_score' => 'float'];

    public function employee()  { return $this->belongsTo(Employee::class); }
    public function reviewer()  { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function scores()    { return $this->hasMany(PerformanceScore::class, 'review_id')->with('category'); }

    /**
     * Recalculate overall_score using weighted formula:
     * overall = sum(score × weight) / sum(weights)
     */
    public function recalculate(): void
    {
        $scores = $this->scores()->with('category')->get();

        $sumWeightedScores = $scores->sum('weighted_score');
        $sumWeights        = $scores->sum(fn($s) => $s->category->weight ?? 1);

        $this->overall_score = $sumWeights > 0
            ? round($sumWeightedScores / $sumWeights, 2)
            : 0;

        $this->save();
    }

    public function gradeBadgeClass(): string
    {
        return match(true) {
            $this->overall_score >= 8  => 'success',
            $this->overall_score >= 6  => 'info',
            $this->overall_score >= 4  => 'warning',
            default                    => 'danger',
        };
    }

    public function gradeLabel(): string
    {
        return match(true) {
            $this->overall_score >= 9  => 'Excellent',
            $this->overall_score >= 7  => 'Good',
            $this->overall_score >= 5  => 'Average',
            $this->overall_score >= 3  => 'Below Average',
            default                    => 'Poor',
        };
    }
}
