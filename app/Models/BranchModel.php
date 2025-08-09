<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchModel extends Model
{
    use HasFactory;
    protected $table = 'tbl_shop_branch';
    protected $guarded = [];
    protected $fillable = [
        'shop_id', 'branch_name', 'branch_location', 'm_name', 'm_email', 'contact', 'status_id', 'created_at', 'updated_at'
    ];
    protected $primaryKey = 'branch_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public function shop()
    {
        return $this->belongsTo(ShopModel::class);
    }
    public function branch_status()
    {
        return $this->belongsTo(ShopModel::class);
    }
}
