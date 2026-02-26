<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTypeModel extends Model
{
    protected $table = 'tbl_order_type';

    protected $primaryKey = 'order_type_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'order_type',
    ];

    public function orders()
    {
        return $this->hasMany(OrdersModel::class, 'order_type_id');
    }
}
