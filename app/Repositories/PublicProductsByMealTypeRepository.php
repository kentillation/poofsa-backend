<?php

namespace App\Repositories;

use App\Models\ProductsModel;
use Illuminate\Database\Eloquent\Builder;

class PublicProductsByMealTypeRepository
{

    public function getAllPublicProductsByMealType($mealType, $perPage)
    {
        $query = ProductsModel::with([
            'shop' => function ($query) {
                $query->select('shop_id', 'shop_name', 'shop_type');
            },
            'size' => function ($query) {
                $query->select('product_size_id', 'size_label');
            },
            'temperature' => function ($query) {
                $query->select('product_temp_id', 'temp_label');
            },
            'category' => function ($query) {
                $query->select('product_category_id', 'category_label', 'product_base_category_id');
            },
            'category.baseCategory' => function ($query) use ($mealType) {
                $query->select('product_base_category_id', 'meal_type');
            }
        ])
            ->where('availability_id', 1)
            ->whereHas('category.baseCategory', function (Builder $query) use ($mealType) {
                $query->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
            })
            ->orderBy('product_name');

        return $query->paginate($perPage ?? 20);
    }
}
