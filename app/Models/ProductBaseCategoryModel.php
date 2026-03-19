<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBaseCategoryModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_base_category';

    protected $primaryKey = 'product_base_category_id';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
