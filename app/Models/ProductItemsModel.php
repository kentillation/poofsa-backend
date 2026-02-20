<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductItemsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_items';

    protected $primaryKey = 'product_item_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'product_id',
        'ingredient_id',
        'ingredient_capital',
        'quantity_required',
        'shop_id',
        'branch_id'
    ];

    public function ingredient()
    {
        return $this->belongsTo(IngredientsModel::class, 'ingredient_id');
    }
}
