<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class CashierModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tbl_cashier';

    protected $primaryKey = 'cashier_id';

    public $incrementing = true;

    protected $fillable = [
        'cashier_name',
        'cashier_email',
        'cashier_password',
        'cashier_mpin',
        'shop_id',
        'branch_id',
        'is_active',
    ];

    protected $hidden = [
        'cashier_password',
        'cashier_mpin',
        'shop_id',
        'branch_id',
    ];

    public function getAuthPassword()
    {
        return $this->cashier_password;
    }

    public function getAuthIdentifierName()
    {
        return 'cashier_email';
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
