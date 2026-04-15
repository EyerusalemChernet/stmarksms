<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'module', 'description', 'ip_address'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Convenience logger */
    public static function log(string $action, string $module, string $description): void
    {
        static::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'ip_address'  => request()->ip(),
        ]);
    }
}
