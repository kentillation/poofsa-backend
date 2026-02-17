<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_variants';

    protected $primaryKey = 'variant_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'product_id',
        'variant_name',
        'price_adjustment',
        'sku',
        'is_active',
    ];
}
