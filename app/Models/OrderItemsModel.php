<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_order_items';

    protected $primaryKey = 'order_item_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
    ];

    public function products()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }

}
