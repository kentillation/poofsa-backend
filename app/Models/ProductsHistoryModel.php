<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsHistoryModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_products_history';

    protected $primaryKey = 'product_history_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'product_id',
        'description',
        'modified_type_id',
        'shop_id',
        'branch_id',
        'user_id',
    ];

    public function products()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }

    // Add to ProductsHistoryModel.php
    public function size()
    {
        return $this->hasOneThrough(
            SizeModel::class,
            ProductsModel::class,
            'product_id', // Foreign key on products table
            'size_id',    // Foreign key on size table
            'product_id', // Local key on products_history
            'size_id'     // Local key on products
        );
    }

    public function temperature()
    {
        return $this->hasOneThrough(
            TemperatureModel::class,
            ProductsModel::class,
            'product_id',    // Foreign key on products table
            'temp_id',       // Foreign key on temperature table
            'product_id',    // Local key on products_history
            'temp_id'        // Local key on products
        );
    }

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

    // Accessors for cleaner resource
    public function getProductNameAttribute()
    {
        return $this->products->product_name ?? null;
    }

    public function getSizeLabelAttribute()
    {
        return $this->products->size->size_label ?? null;
    }

    public function getTempLabelAttribute()
    {
        return $this->products->temperature->temp_label ?? null;
    }

    public function getModifiedTypeAttribute()
    {
        return $this->modify->modified_type ?? null;
    }

    public function getAdminNameAttribute()
    {
        return $this->users->admin_name ?? null;
    }
}
