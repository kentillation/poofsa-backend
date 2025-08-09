<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionOrdersModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_transaction_orders';

    protected $guarded = [];

    protected $fillable = [
        'transaction_id',
        'station_id',
        'product_id',
        'quantity',
        'station_status_id', // for WS
        'created_at',
        'updated_at',
    ];

    protected $primaryKey = 'transaction_order_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public function transaction()
    {
        return $this->belongsTo(TransactionModel::class, 'transaction_id', 'transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id', 'product_id');
    }

    public function station()
    {
        return $this->belongsTo(StationModel::class, 'station_id', 'station_id');
    }

    public function stationStatus()
    {
        return $this->belongsTo(StationStatusModel::class, 'station_status_id', 'station_status_id');
    }
}
