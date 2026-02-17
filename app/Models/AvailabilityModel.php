<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilityModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_availability';

    protected $primaryKey = 'product_availability_id';

    protected $fillable = [
        'product_availability_id', 'availability_label',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
