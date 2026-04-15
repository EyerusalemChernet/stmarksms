<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class BookRequest extends Model
{
    protected $fillable = ['book_id', 'user_id', 'status', 'start_date', 'end_date', 'returned', 'issued_at', 'returned_at', 'requested_at'];

    public function book() { return $this->belongsTo(Book::class); }
    public function user() { return $this->belongsTo(User::class); }
}
