<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemperatureModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_temp';

    protected $primaryKey = 'product_temp_id';

    protected $fillable = [
        'product_temp_id', 'temp_label',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
