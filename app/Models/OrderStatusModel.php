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

    protected $casts = [
        'order_status_id' => 'integer',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function transactions()
    {
        return $this->hasMany(OrdersModel::class, 'order_status_id', 'order_status_id');
    }
}
