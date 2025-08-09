<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_order_status';

    protected $guarded = [];

    protected $primaryKey = 'order_status_id';

    protected $keyType = 'int';

    public function transactions()
    {
        return $this->hasMany(TransactionModel::class, 'order_status_id', 'order_status_id');
    }
}
