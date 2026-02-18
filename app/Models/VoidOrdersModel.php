<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoidOrdersModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_void_orders';

    protected $primaryKey = 'void_order_id';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'order_id',
        'product_id',
        'reference_number',
        'void_reason',
        'void_notes',
        'voided_by',
        'voided_at',
        'void_status_id',
        'from_quantity',
        'to_quantity',
        'shop_id',
        'branch_id',
    ];

    public function orders()
    {
        return $this->belongsTo(OrdersModel::class, 'order_id');
    }

    public function voidedByUsers()
    {
        return $this->belongsTo(CashierModel::class, 'voided_by');
    }
}
