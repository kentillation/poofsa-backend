<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_category';

    protected $primaryKey = 'product_category_id';

    public $incrementing = true;

    protected $fillable = [
        'product_category_id', 
        'category_label', 
        'product_base_category_id',
        'shop_id'
    ];

    protected $hidden = [
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function baseCategory()
    {
        return $this->belongsTo(ProductBaseCategoryModel::class, 'product_base_category_id');
    }

    public function products()
    {
        return $this->hasMany(ProductsModel::class, 'category_id');
    }

}
