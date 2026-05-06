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

    protected $primaryKey = 'admin_id';

    public $incrementing = true;

    protected $fillable = [
        'admin_name',
        'admin_email',
        'admin_password',
        'admin_mpin',
        'shop_id',
        'role',
        'status',
        'recovery_code',
        'recovery_attempts',
        'recovery_code_used_at',
        'recovery_code_expires_at'
    ];

    protected $hidden = [
        'admin_password',
        'admin_mpin',
        'shop_id',
    ];

    // Overriding 'password'
    public function getAuthPassword()
    {
        return $this->admin_password;
    }

    // protected $passwordField = 'admin_password';

    // public function setPasswordAttribute($value)
    // {
    //     $this->attributes['admin_password'] = $value;
    // }

    // public function getAuthIdentifierName()
    // {
    //     return 'admin_email';
    // }

    public function shop()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id', 'shop_id');
    }
}
