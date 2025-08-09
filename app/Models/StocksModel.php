<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AvailabilityModel;
use App\Models\UnitModel;
/**
 * StocksModel
 *
 * @property int $stock_id
 * @property string $stock_ingredient
 * @property int $stock_unit
 * @property float $stock_in
 * @property float $stock_out
 * @property float $stock_cost_per_unit
 * @property int $availability_id
 * @property int $stock_alert_qty
 * @property int $shop_id
 * @property int $branch_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class StocksModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_stocks';

    protected $guarded = [];

    protected $fillable = [
        'stock_ingredient', 'stock_unit', 'stock_in', 'stock_cost_per_unit', 'availability_id', 'stock_alert_qty', 'shop_id', 'branch_id', 'user_id', 'created_at', 'updated_at',
    ];

    protected $primaryKey = 'stock_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public function ingredients()
    {
        return $this->hasMany(IngredientsModel::class, 'stock_id', 'stock_id');
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
