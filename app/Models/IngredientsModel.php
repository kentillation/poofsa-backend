<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_ingredients';

    protected $primaryKey = 'ingredient_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'ingredient_name',
        'base_unit_id',
        'alert_quantity',
        'shop_id',
        'branch_id'
    ];

    public function batches()
    {
        return $this->hasMany(StockBatchesModel::class, 'ingredient_id');
    }

    public function shops()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id', 'shop_id');
    }

    public function branches()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id', 'branch_id');
    }
}
