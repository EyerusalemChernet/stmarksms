<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['author_id', 'title', 'body', 'audience', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
