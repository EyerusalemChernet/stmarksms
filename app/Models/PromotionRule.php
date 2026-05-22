<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PromotionRule extends Model
{
    protected $table = 'promotion_rules';

    protected $fillable = [
        'name', 'rule_type', 'condition_operator', 'threshold_value',
        'scope_type', 'scope_class_id', 'scope_department_id', 'scope_year',
        'is_active', 'description', 'created_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'threshold_value' => 'float',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function scopeClass()      { return $this->belongsTo(MyClass::class, 'scope_class_id'); }
    public function scopeDepartment() { return $this->belongsTo(Department::class, 'scope_department_id'); }
    public function createdBy()       { return $this->belongsTo(User::class, 'created_by'); }

    // ── Query scopes ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', 1);
    }

    public function scopeForClass(Builder $q, int $classId): Builder
    {
        return $q->where('scope_type', 'class')->where('scope_class_id', $classId);
    }

    public function scopeForDepartment(Builder $q, int $deptId): Builder
    {
        return $q->where('scope_type', 'department')->where('scope_department_id', $deptId);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Human-readable label for the rule type */
    public function ruleTypeLabel(): string
    {
        return match($this->rule_type) {
            'min_overall_average'    => 'Minimum Overall Average',
            'core_subject_min_score' => 'Core Subject Minimum Score',
            'max_failed_subjects'    => 'Maximum Failed Subjects',
            'min_attendance_rate'    => 'Minimum Attendance Rate',
            'fee_clearance_required' => 'Fee Clearance Required',
            'discipline_restriction' => 'Discipline Restriction',
            'conditional_promotion'  => 'Conditional Promotion',
            default                  => ucwords(str_replace('_', ' ', $this->rule_type)),
        };
    }

    /** Human-readable operator label */
    public function operatorLabel(): string
    {
        return match($this->condition_operator) {
            'gte' => '≥',
            'lte' => '≤',
            'gt'  => '>',
            'lt'  => '<',
            'eq'  => '=',
            default => '',
        };
    }

    /** Scope label for display */
    public function scopeLabel(): string
    {
        return match($this->scope_type) {
            'class'      => 'Class: ' . ($this->scopeClass?->name ?? '—'),
            'department' => 'Dept: ' . ($this->scopeDepartment?->name ?? '—'),
            'year'       => 'Year: ' . ($this->scope_year ?? '—'),
            default      => 'Entire School',
        };
    }

    /** Whether this rule type uses a threshold value */
    public function hasThreshold(): bool
    {
        return !in_array($this->rule_type, ['fee_clearance_required', 'discipline_restriction']);
    }

    /** Icon for the rule type */
    public function ruleIcon(): string
    {
        return match($this->rule_type) {
            'min_overall_average'    => 'bi-bar-chart-line',
            'core_subject_min_score' => 'bi-book',
            'max_failed_subjects'    => 'bi-x-circle',
            'min_attendance_rate'    => 'bi-calendar-check',
            'fee_clearance_required' => 'bi-cash-coin',
            'discipline_restriction' => 'bi-shield-exclamation',
            'conditional_promotion'  => 'bi-arrow-up-circle',
            default                  => 'bi-gear',
        };
    }
}
