<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class KitchenModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'tbl_kitchen';
    protected $guarded = [];
    protected $primaryKey = 'kitchen_id';
    protected $fillable = [
        'kitchen_name',
        'kitchen_email',
        'kitchen_password',
        'kitchen_mpin',
        'shop_id',
        'branch_id',
    ];
    protected $hidden = [
        'kitchen_password',
        'kitchen_mpin',
        'shop_id',
        'branch_id',
    ];
    public function getAuthPassword()
    {
        return $this->kitchen_password;
    }
    public function getAuthIdentifierName()
    {
        return 'kitchen_email';
    }
    public function shop()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id');
    }
    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id');
    }
}
