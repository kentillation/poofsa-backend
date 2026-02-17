<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_category';

    protected $primaryKey = 'product_category_id';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'category_name',
        'shop_id',
        'is_active',
    ];

}
