<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_products';

    protected $primaryKey = 'product_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'product_name',
        'sku',
        'size_id',
        'temp_id',
        'category_id',
        'base_price',
        'cost_estimate',
        'is_active',
        'is_new',
        'availability_id',
        'station_id',
        'shop_id',
        'branch_id',
        'user_id',
        'thumbnail_path',
        'standard_image_path',
        'image_size_kb'
    ];

    protected $casts = [
        'size_id' => 'integer',
        'temp_id' => 'integer',
        'category_id' => 'integer',
        'station_id' => 'integer',
        'availability_id' => 'integer'
    ];

    public function shop()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id', 'shop_id');
    }

    public function productItems()
    {
        return $this->hasMany(ProductItemsModel::class, 'product_id');
    }

    public function size()
    {
        return $this->belongsTo(SizeModel::class, 'size_id');
    }

    public function temperature()
    {
        return $this->belongsTo(TemperatureModel::class, 'temp_id');
    }

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function availability()
    {
        return $this->belongsTo(AvailabilityModel::class, 'availability_id');
    }

    public function stations()
    {
        return $this->belongsTo(StationModel::class, 'station_id');
    }

    // Add accessors for URLs
    public function getThumbnailUrlAttribute()
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        return asset('storage/' . $this->thumbnail_path);
    }

    public function getStandardImageUrlAttribute()
    {
        if (!$this->standard_image_path) {
            return null;
        }
        return asset('storage/' . $this->standard_image_path);
    }

    // public function updateAvailabilityBasedOnIngredients()
    // {
    //     $allIngredientsAvailable = true;

    //     foreach ($this->ingredients as $ingredient) {
    //         if ($ingredient->stock->availability_id != 1) {
    //             $allIngredientsAvailable = false;
    //             break;
    //         }
    //     }

    //     $newAvailability = $allIngredientsAvailable ? 1 : 2;

    //     if ($this->availability_id != $newAvailability) {
    //         $this->update(['availability_id' => $newAvailability]);
    //         return true;
    //     }

    //     return false;
    // }

    // public function ingredients()
    // {
    //     return $this->belongsToMany(
    //         ProductItemsModel::class,
    //         'tbl_product_items',
    //         'product_id',
    //         'ingredient_id'
    //     )->withPivot('quantity_required');
    // }
}
