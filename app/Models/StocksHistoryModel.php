<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StocksHistoryModel extends Model
{
    use HasFactory;
    protected $table = 'tbl_stocks_history';

    protected $primaryKey = 'stock_history_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'ingredient_id',
        'description',
        'modified_type_id',
        'shop_id',
        'branch_id',
        'user_id',
    ];

    public function ingredient()
    {
        return $this->belongsTo(IngredientsModel::class, 'ingredient_id');
    }

    public function modify()
    {
        return $this->belongsTo(ModifiedTypeModel::class, 'modified_type_id');
    }

    public function ingredients()
    {
        return $this->belongsTo(IngredientsModel::class, 'ingredient_id');
    }

    public function shops()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id');
    }

    public function branches()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id');
    }
}
