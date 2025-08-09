<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_ingredient';

    protected $guarded = [];

    protected $fillable = [
        'product_id', 'stock_id', 'unit_usage', 'ingredient_capital', 'shop_id', 'branch_id', 'created_at', 'updated_at',
    ];

    protected $primaryKey = 'product_ingredient_id';

    public $incrementing = true;

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id', 'product_id');
    }

    public function stock()
    {
        return $this->belongsTo(StocksModel::class, 'stock_id', 'stock_id');
    }

    public function shop()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id', 'shop_id');
    }

    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id', 'branch_id');
    }
}