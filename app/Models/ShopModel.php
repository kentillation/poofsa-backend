<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopModel extends Model
{
    use HasFactory;
    protected $table = 'tbl_shop';
    protected $primaryKey = 'shop_id';
    protected $guarded = [];
    protected $fillable = [
        'shop_name', 'shop_owner', 'shop_location', 'shop_email', 'shop_contact_number', 'shop_theme', 'shop_status_id', 'created_at', 'updated_at'
    ];
    public $incrementing = true;
    protected $keyType = 'int';
}
