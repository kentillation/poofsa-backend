<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantModel extends Model
{
    protected $table = 'tbl_product_variants';

    protected $primaryKey = 'variant_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'product_id',
        'variant_name',
        'price_adjustment',
        'variant_stock',
        'sku',
        'is_active',
    ];
}
