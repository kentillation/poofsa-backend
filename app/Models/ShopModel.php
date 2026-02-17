<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_shops';

    protected $primaryKey = 'shop_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'shop_name',
        'shop_owner',
        'shop_address',
        'shop_email',
        'shop_contact_number',
        'is_active',
    ];

}
