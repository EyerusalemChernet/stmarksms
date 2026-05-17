<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BookRequest extends Model
{
    protected $fillable = [
        'book_id', 'user_id', 'status',
        'start_date', 'end_date', 'returned',
        'issued_at', 'returned_at', 'requested_at',
        'due_date', 'overdue_fine', 'notes',
    ];

    protected $casts = [
        'issued_at'    => 'datetime',
        'returned_at'  => 'datetime',
        'requested_at' => 'datetime',
        'due_date'     => 'date',
    ];

    public function book() { return $this->belongsTo(Book::class); }
    public function user() { return $this->belongsTo(User::class); }

    /** Is this request overdue? */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'approved'
            && $this->due_date
            && $this->due_date->isPast();
    }

    /** Days overdue (0 if not overdue) */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return (int) $this->due_date->diffInDays(now());
    }

    /** Status badge colour */
    public function statusBadge(): string
    {
        return match($this->status) {
            'approved' => $this->is_overdue ? 'danger' : 'success',
            'rejected' => 'danger',
            'returned' => 'secondary',
            default    => 'warning',
        };
    }

    /** Human-readable status label */
    public function statusLabel(): string
    {
        if ($this->status === 'approved' && $this->is_overdue) return 'Overdue';
        return ucfirst($this->status ?? 'Pending');
    }
}
