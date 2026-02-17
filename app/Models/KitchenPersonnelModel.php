<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class KitchenPersonnelModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tbl_kitchen_personnel';

    protected $primaryKey = 'kitchen_personnel_id';

    protected $fillable = [
        'kitchen_personnel_name',
        'kitchen_personnel_email',
        'kitchen_personnel_password',
        'kitchen_personnel_mpin',
        'shop_id',
        'branch_id',
        'is_active',
    ];

    protected $hidden = [
        'kitchen_personnel_password',
        'kitchen_personnel_mpin',
        'shop_id',
        'branch_id',
    ];

    public function getAuthPassword()
    {
        return $this->kitchen_password;
    }

    public function getAuthIdentifierName()
    {
        return 'kitchen_personnel_email';
    }

    public function shops()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id');
    }

    public function branches()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id');
    }
}
