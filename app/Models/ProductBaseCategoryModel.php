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

    public $fillable = [
        'product_base_category',
        'category_subtitle_hiligaynon',
        'category_subtitle_bisaya',
        'category_subtitle_tagalog',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
