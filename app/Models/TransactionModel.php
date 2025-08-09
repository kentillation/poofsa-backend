<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_transaction';

    protected $guarded = [];

    protected $fillable = [
        'reference_number', 'table_number', 'customer_name', 'total_quantity', 'customer_cash', 'customer_charge', 'customer_change',  'customer_discount', 'computed_discount', 'total_due', 'order_status_id', 'user_id', 'shop_id', 'branch_id', 'created_at', 'updated_at',
    ];

    protected $primaryKey = 'transaction_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public function user()
    {
        return $this->belongsTo(AdminModel::class);
    }

    public function shop()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id', 'shop_id');
    }

    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id', 'branch_id');
    }

    public function orders()
    {
        return $this->hasMany(TransactionOrdersModel::class, 'transaction_id', 'transaction_id');
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatusModel::class, 'order_status_id', 'order_status_id');
    }
}
