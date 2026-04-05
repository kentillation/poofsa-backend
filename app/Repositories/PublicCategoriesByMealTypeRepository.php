<?php

namespace App\Repositories;

use App\Models\CategoryModel;
use Illuminate\Database\Eloquent\Builder;

class PublicCategoriesByMealTypeRepository
{

    public function getAllPublicCategoriesByMealType($mealType, $perPage, $search = null)
    {
        $query = CategoryModel::with('baseCategory')
            ->where('availability_id', 1)
            ->whereHas('baseCategory', function (Builder $query) use ($mealType) {
                $query->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
            })
            ->when($perPage, function ($queryPaginate) use ($perPage) {
                $queryPaginate->paginate($perPage ?? 20);
            })
            ->when($search, function ($querySearch) use ($search) {
                $querySearch->where('product_name', 'like', '%' . $search . '%');
            })
            ->orderBy('category_label', 'asc')
            ->distinct('category_label');

        return $query;
    }
}
