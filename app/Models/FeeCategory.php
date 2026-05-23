<?php
namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'active',
        'admin_updated_by', 'admin_action', 'admin_update_note', 'admin_updated_at',
    ];

    protected $casts = [
        'admin_updated_at' => 'datetime',
    ];

    public function structures()
    {
        return $this->hasMany(FeeStructure::class, 'fee_category_id');
    }

    public function adminUpdater()
    {
        return $this->belongsTo(User::class, 'admin_updated_by');
    }
}
