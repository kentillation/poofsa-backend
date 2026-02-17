<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StocksModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_stocks';

    protected $primaryKey = 'stock_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'stock_ingredient', 'stock_unit', 'stock_in', 'stock_unit_cost', 'availability_id', 'stock_alert_qty', 'shop_id', 'branch_id', 'user_id', 'created_at', 'updated_at',
    ];

    public function ingredients()
    {
        return $this->hasMany(ProductIngredientsModel::class, 'stock_id', 'stock_id');
    }

    public function unit()
    {
        return $this->belongsTo(UnitModel::class);
    }

    public function availability()
    {
        return $this->belongsTo(AvailabilityModel::class);
    }

    public function branches()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id', 'branch_id');
    }
}
