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
        'reference_number',
        'order_type',
        'order_status',
        'table_number',
        'order_note',
        'shop_id',
        'branch_id',
        'user_id',
    ];

    public function items()
    {
        return $this->hasMany(OrderItemsModel::class, 'order_id');
    }

    public function sale()
    {
        return $this->hasOne(SalesModel::class, 'order_id');
    }
}
