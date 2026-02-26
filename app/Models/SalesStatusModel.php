<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesStatusModel extends Model
{
    protected $table = 'tbl_sales_status';

    protected $primaryKey = 'sales_status_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'sales_status',
    ];
}
