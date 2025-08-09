<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionVoidModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_transaction_void';

    protected $guarded = [];

    protected $fillable = [
        'reference_number',
        'transaction_id',
        'table_number',
        'product_id',
        'from_quantity',
        'to_quantity',
        'void_status_id',
        'user_id',
        'shop_id',
        'branch_id',
        'created_at',
        'updated_at',
    ];

    protected $primaryKey = 'transaction_void_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public function transaction()
    {
        return $this->belongsTo(TransactionModel::class, 'transaction_id', 'transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id', 'product_id');
    }

    public function voidStatus()
    {
        return $this->belongsTo(VoidStatusModel::class, 'void_status_id', 'void_status_id');
    }
}
