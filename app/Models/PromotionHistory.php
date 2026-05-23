<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PromotionHistory extends Model
{
    protected $table = 'promotion_history';

    // Append-only — no updated_at
    public $timestamps = false;

    protected $fillable = [
        'promotion_batch_id', 'student_id',
        'old_enrollment_id', 'new_enrollment_id',
        'old_class_id', 'old_section_id', 'old_session',
        'action_type', 'action_date', 'performed_by',
        'created_at',
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'created_at'  => 'datetime',
    ];

    // ── Append-only guard ────────────────────────────────────────────────────

    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \RuntimeException('PromotionHistory records are append-only and cannot be updated.');
        }
        $this->created_at = $this->created_at ?? now();
        return parent::save($options);
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function batch()       { return $this->belongsTo(PromotionBatch::class, 'promotion_batch_id'); }
    public function student()     { return $this->belongsTo(User::class, 'student_id'); }
    public function performedBy() { return $this->belongsTo(User::class, 'performed_by'); }
}
