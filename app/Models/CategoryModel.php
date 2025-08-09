<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_category';
    protected $guarded = [];
    protected $primaryKey = 'category_id';
    protected $fillable = [
        'category_id', 'category_label',
    ];
    protected $visible = [
        'category_id', 'category_label',
    ];
}
