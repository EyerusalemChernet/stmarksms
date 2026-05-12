<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ApplicationNote extends Model
{
    protected $fillable = ['application_id', 'user_id', 'status_changed_to', 'note'];

    public function application() { return $this->belongsTo(JobApplication::class); }
    public function author()      { return $this->belongsTo(User::class, 'user_id'); }
}
