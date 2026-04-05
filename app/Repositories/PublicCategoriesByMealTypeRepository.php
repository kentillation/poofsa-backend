<?php

namespace App\Repositories;

use App\Models\CategoryModel;
use Illuminate\Database\Eloquent\Builder;

class PublicCategoriesByMealTypeRepository
{

    public function getAllPublicCategoriesByMealType($mealType, $perPage, $search = null)
    {
        $query = CategoryModel::with('baseCategory')
            ->whereHas('baseCategory', function (Builder $query) use ($mealType) {
                $query->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
            })
            ->when($search, function ($querySearch) use ($search) {
                $querySearch->where('product_name', 'like', '%' . $search . '%');
            })
            ->select('category_label', 'product_base_category_id') // Select specific columns
            ->distinct('category_label')
            ->orderBy('category_label', 'asc');

        return $query->paginate($perPage ?? 20);
    }
}
