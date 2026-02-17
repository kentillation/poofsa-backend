<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItemsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_sale_items';

    protected $primaryKey = 'sale_item_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'sale_id',
        'product_id',
        'variant_id',
        'product_name_snapshot',
        'variant_name_snapshot',
        'quantity',
        'unit_price',
        'total_price',
    ];

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }
}
