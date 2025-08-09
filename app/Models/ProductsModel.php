<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TemperatureModel;
use App\Models\SizeModel;
use App\Models\CategoryModel;
use App\Models\AvailabilityModel;
use App\Models\StationModel;

/**
 * ProductsModel
 *
 * @property int $product_id
 * @property string $product_name
 * @property float $product_price
 * @property int $product_temp_id
 * @property int $product_size_id
 * @property int $product_category_id
 * @property int $availability_id
 * @property int $visibility_id
 * @property int $shop_id
 * @property int $branch_id
 * @property int $user_id
 */
class ProductsModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_products';

    protected $guarded = [];

    protected $fillable = [
        'product_id',
        'product_name',
        'product_price',
        'product_temp_id',
        'product_size_id',
        'product_category_id',
        'station_id',
        'availability_id',
        'visibility_id',
        'shop_id',
        'branch_id',
        'user_id',
        'created_at',
        'updated_at',
    ];

    protected $primaryKey = 'product_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public function temperature()
    {
        return $this->belongsTo(TemperatureModel::class, 'product_temp_id', 'temp_id');
    }

    public function size()
    {
        return $this->belongsTo(SizeModel::class, 'product_size_id', 'size_id');
    }

    public function category()
    {
        return $this->belongsTo(CategoryModel::class);
    }

    public function availability()
    {
        return $this->belongsTo(AvailabilityModel::class);
    }

    public function station()
    {
        return $this->belongsTo(StationModel::class);
    }

    public function updateAvailabilityBasedOnIngredients()
    {
        $allIngredientsAvailable = true;

        foreach ($this->ingredients as $ingredient) {
            if ($ingredient->stock->availability_id != 1) {
                $allIngredientsAvailable = false;
                break;
            }
        }

        $newAvailability = $allIngredientsAvailable ? 1 : 2;

        if ($this->availability_id != $newAvailability) {
            $this->update(['availability_id' => $newAvailability]);
            return true; // Availability was changed
        }

        return false; // Availability was not changed
    }
}
