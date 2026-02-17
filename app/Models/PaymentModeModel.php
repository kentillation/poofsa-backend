<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModeModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_payment_mode';

    protected $primaryKey = 'payment_mode_id';

    protected $keyType = 'int';

    public function transactions()
    {
        return $this->hasMany(OrdersModel::class, 'payment_mode_id', 'payment_mode_id');
    }
}
