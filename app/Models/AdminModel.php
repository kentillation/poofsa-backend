<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class AdminModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'tbl_admin';
    protected $guarded = [];
    protected $primaryKey = 'admin_id';
    protected $fillable = [
        'admin_name',
        'admin_email',
        'admin_password',
        'admin_mpin',
        'shop_id',
    ];
    protected $hidden = [
        'admin_password',
        'admin_mpin',
        'shop_id',
    ];
    public function getAuthPassword()
    {
        return $this->admin_password;
    }
    public function getAuthIdentifierName()
    {
        return 'admin_email';
    }
    public function shop()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id');
    }
}
