<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentRecord extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'session', 'user_id', 'my_class_id', 'section_id', 'my_parent_id',
        'adm_no', 'year_admitted', 'wd', 'wd_date', 'grad', 'grad_date',
        'religion', 'age',
    ];

    public function user()       { return $this->belongsTo(User::class); }
    public function my_parent()  { return $this->belongsTo(User::class); }
    public function my_class()   { return $this->belongsTo(MyClass::class); }
    public function section()    { return $this->belongsTo(Section::class); }

    /**
     * Return the student's current age.
     * Prefers a live calculation from dob if available,
     * falls back to the stored age column.
     */
    public function getCurrentAgeAttribute(): ?int
    {
        if ($this->user && $this->user->dob) {
            try {
                return Carbon::parse($this->user->dob)->age;
            } catch (\Exception $e) {
                // fall through
            }
        }
        return $this->age;
    }
}
