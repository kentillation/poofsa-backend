<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_sales';

    protected $primaryKey = 'sale_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'receipt_no',
        'order_id',
        'shop_id',
        'branch_id',
        'user_id',
        'payment_method_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'sale_status',
    ];

    public function items()
    {
        return $this->hasMany(SaleItemsModel::class, 'sale_id');
    }

    public function orders()
    {
        return $this->belongsTo(OrdersModel::class, 'order_id');
    }
}
