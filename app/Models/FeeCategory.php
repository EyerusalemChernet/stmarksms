<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    protected $fillable = ['name', 'code', 'description', 'active'];

    public function structures()
    {
        return $this->hasMany(FeeStructure::class, 'fee_category_id');
    }
}
