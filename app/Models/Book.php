<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'name', 'isbn', 'my_class_id', 'description', 'author',
        'publisher', 'published_year', 'cover_image',
        'book_type', 'subject_area', 'url', 'location',
        'total_copies', 'issued_copies', 'due_days',
    ];

    public function my_class() { return $this->belongsTo(MyClass::class); }
    public function requests() { return $this->hasMany(BookRequest::class, 'book_id'); }

    /** Available copies right now */
    public function getAvailableCopiesAttribute(): int
    {
        return max(0, (int)$this->total_copies - (int)$this->issued_copies);
    }

    /** True if at least one copy is available */
    public function getIsAvailableAttribute(): bool
    {
        return $this->available_copies > 0;
    }

    /** Availability badge colour */
    public function availabilityBadge(): string
    {
        $a = $this->available_copies;
        if ($a === 0)  return 'danger';
        if ($a <= 2)   return 'warning';
        return 'success';
    }

    /** Cover image URL — falls back to a generated placeholder */
    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image && file_exists(public_path('uploads/library/' . $this->cover_image))) {
            return asset('uploads/library/' . $this->cover_image);
        }
        // Colour-coded placeholder based on book type
        $colours = [
            'Textbook'  => '4f46e5',
            'Reference' => '0891b2',
            'Novel'     => '059669',
            'Magazine'  => 'db2777',
            'Other'     => '64748b',
        ];
        $colour = $colours[$this->book_type ?? 'Other'] ?? '64748b';
        $initials = strtoupper(substr($this->name, 0, 2));
        return "https://ui-avatars.com/api/?name={$initials}&background={$colour}&color=fff&size=80&bold=true&font-size=0.4";
    }
}
