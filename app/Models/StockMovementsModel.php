<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovementsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_stock_movements';

    protected $primaryKey = 'stock_movement_id';

    public $incrementing = true;

    protected $fillable = [
        'ingredient_id',
        'stock_batch_id',
        'movement_type',
        'quantity',
        'reference_type', // sale, purchase, spoilage
        'reference_id',
        'user_id',
    ];

    public function batch()
    {
        return $this->belongsTo(StockBatchesModel::class, 'stock_batch_id');
    }
}
