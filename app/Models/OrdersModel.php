<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdersModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_orders';

    protected $primaryKey = 'order_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'order_number',
        'table_number',
        'customer_name',
        'reference_number',
        'customer_cash',
        'customer_change',
        'order_type_id',
        'order_status_id',
        'order_note',
        'total_quantity',
        'shop_id',
        'branch_id',
        'user_id',
    ];

    // protected $casts = ('total_quantity')->int;

    public function items()
    {
        return $this->hasMany(OrderItemsModel::class, 'order_id');
    }

    public function sale()
    {
        return $this->hasOne(SalesModel::class, 'order_id');
    }

    public function orderType()
    {
        return $this->belongsTo(OrderTypeModel::class, 'order_type_id');
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatusModel::class, 'order_status_id');
    }

    public function shop()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id');
    }

    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id');
    }

    public function cashier()
    {
        return $this->belongsTo(CashierModel::class, 'user_id');
    }
}
