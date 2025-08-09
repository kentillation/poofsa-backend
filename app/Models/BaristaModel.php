<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class BaristaModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'tbl_barista';
    protected $guarded = [];
    protected $primaryKey = 'barista_id';
    protected $fillable = [
        'barista_name',
        'barista_email',
        'barista_password',
        'barista_mpin',
        'shop_id',
        'branch_id',
    ];
    protected $hidden = [
        'barista_password',
        'barista_mpin',
        'shop_id',
        'branch_id',
    ];
    public function getAuthPassword()
    {
        return $this->barista_password;
    }
    public function getAuthIdentifierName()
    {
        return 'barista_email';
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
