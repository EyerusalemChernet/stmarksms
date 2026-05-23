<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PromotionBatch extends Model
{
    protected $table = 'promotion_batches';

    protected $fillable = [
        'from_academic_year_id', 'to_academic_year_id',
        'from_class_id', 'to_class_id',
        'redistribution_mode', 'status', 'student_count',
        'created_by', 'finalized_at',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function fromYear()   { return $this->belongsTo(AcademicYear::class, 'from_academic_year_id'); }
    public function toYear()     { return $this->belongsTo(AcademicYear::class, 'to_academic_year_id'); }
    public function fromClass()  { return $this->belongsTo(MyClass::class, 'from_class_id'); }
    public function toClass()    { return $this->belongsTo(MyClass::class, 'to_class_id'); }
    public function createdBy()  { return $this->belongsTo(User::class, 'created_by'); }
    public function drafts()     { return $this->hasMany(PromotionDraft::class, 'promotion_batch_id'); }
    public function history()    { return $this->hasMany(PromotionHistory::class, 'promotion_batch_id'); }

    // ── Query scopes ─────────────────────────────────────────────────────────

    public function scopeDraft($query)     { return $query->where('status', 'draft'); }
    public function scopeFinalized($query) { return $query->where('status', 'finalized'); }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isFinalized(): bool  { return $this->status === 'finalized'; }
    public function isRolledBack(): bool { return $this->status === 'rolled_back'; }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'finalized'   => 'badge-success',
            'rolled_back' => 'badge-danger',
            default       => 'badge-primary',
        };
    }
}
