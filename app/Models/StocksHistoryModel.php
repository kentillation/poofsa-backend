<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StocksHistoryModel extends Model
{
    use HasFactory;
    protected $table = 'tbl_stocks_history';
    protected $guarded = [];
    protected $fillable = [
        'stock_id', 'description', 'manage_id', 'shop_id', 'branch_id', 'user_id', 'created_at', 'updated_at',
    ];
    public function stocks()
    {
        return $this->belongsTo(StocksModel::class);
    }
}
