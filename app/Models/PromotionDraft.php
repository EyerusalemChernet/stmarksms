<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PromotionDraft extends Model
{
    protected $table = 'promotion_drafts';

    protected $fillable = [
        'promotion_batch_id', 'student_id',
        'current_section_id', 'proposed_section_id',
        'is_locked', 'redistribution_group',
        'eligibility_status', 'yearly_average', 'remarks',
    ];

    protected $casts = [
        'is_locked'      => 'boolean',
        'yearly_average' => 'float',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function batch()           { return $this->belongsTo(PromotionBatch::class, 'promotion_batch_id'); }
    public function student()         { return $this->belongsTo(User::class, 'student_id'); }
    public function currentSection()  { return $this->belongsTo(Section::class, 'current_section_id'); }
    public function proposedSection() { return $this->belongsTo(Section::class, 'proposed_section_id'); }
}
