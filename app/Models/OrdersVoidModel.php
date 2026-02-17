<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdersVoidModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_orders_void';

    protected $primaryKey = 'order_void_id';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'order_id',
        'void_reason',
        'void_notes',
        'voided_by',
        'voided_at',
        'void_status',
        'from_quantity',
        'to_quantity',
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
