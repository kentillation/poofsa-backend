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
        'payment_method_id',
        'order_type_charge',
        'total_amount',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'sales_status_id',
        'shop_id',
        'branch_id',
        'user_id',
    ];

    public function items()
    {
        return $this->hasMany(SaleItemsModel::class, 'sale_id');
    }

    public function orders()
    {
        return $this->belongsTo(OrdersModel::class, 'order_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethodModel::class, 'payment_method_id');
    }

    public function salesStatus()
    {
        return $this->belongsTo(SalesStatusModel::class, 'sales_status_id');
    }
}
