<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatchesModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_stock_batches';

    protected $primaryKey = 'stock_batch_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'ingredient_id',
        'batch_code',
        'expiry_date',
        'unit_cost',
        'quantity_received',
        'quantity_remaining',
        'shop_id',
        'branch_id',
    ];

    public function ingredient()
    {
        return $this->belongsTo(IngredientsModel::class, 'ingredient_id');
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
