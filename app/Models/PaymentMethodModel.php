<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_payment_method';

    protected $primaryKey = 'payment_method_id';

    protected $fillable = [
        'payment_method_name',
    ];
}
