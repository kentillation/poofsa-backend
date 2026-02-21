<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_products';

    protected $primaryKey = 'product_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'product_id',
        'product_name',
        'sku',
        'size_id',
        'temp_id',
        'category_id',
        'base_price',
        'cost_estimate',
        'is_active',
        'availability_id',
        'station_id',
        'shop_id',
        'branch_id',
        'user_id',
    ];

    public function ingredients()
    {
        return $this->belongsToMany(
            ProductItemsModel::class,
            'tbl_product_items',
            'product_id',
            'ingredient_id'
        )->withPivot('quantity_required');
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
}
