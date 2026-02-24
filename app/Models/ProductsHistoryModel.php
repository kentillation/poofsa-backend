<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsHistoryModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_products_history';

    protected $fillable = [
        'product_id', 'description', 'modified_type_id', 'shop_id', 'branch_id', 'user_id',
    ];

    public function modify()
    {
        return $this->belongsTo(ModifiedTypeModel::class, 'modified_type_id');
    }

    public function shops()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id');
    }

    public function branches()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id');
    }

    public function users()
    {
        return $this->belongsTo(AdminModel::class, 'user_id');
    }
}
