<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'payment_intent_id',
    //     'paymongo_payment_id',
    //     'amount',
    //     'status',
    //     'paid_at',
    //     'created_at',
    //     'updated_at',
    // ];

    protected $table = 'tbl_payment';

    protected $primaryKey = 'payment_id';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'payment_intent_id',
        'idempotency_key',
        'reference_number',
        'paymongo_payment_id',
        'amount',
        'status',
        'paid_at',
    ];

}
