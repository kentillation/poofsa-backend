<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_size';

    protected $primaryKey = 'product_size_id';

    protected $fillable = [
        'product_size_id', 'size_label',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
