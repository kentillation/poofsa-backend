<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPricesModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_prices';

    protected $primaryKey = 'price_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'product_id',
        'variant_id',
        'price',
        'effective_from',
        'effective_to',
        'user_id',
    ];
}
