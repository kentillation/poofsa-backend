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
        'shop_type',
        'shop_owner',
        'shop_address',
        'shop_email',
        'shop_contact_number',
        'is_active',
        'open_at',
        'close_at'
    ];

    public function products()
    {
        return $this->hasMany(ProductsModel::class, 'shop_id');
    }

    public function lowestPricedProduct()
    {
        return $this->hasOne(ProductsModel::class, 'shop_id')
            ->where('availability_id', 1)
            ->orderBy('base_price', 'asc')
            ->orderBy('product_id', 'asc');
    }

}
