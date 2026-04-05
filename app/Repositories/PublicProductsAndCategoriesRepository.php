<?php

namespace App\Repositories;

use App\Models\ProductsModel;
use App\Models\CategoryModel;


class PublicProductsAndCategoriesRepository
{
    public function getAllPublicNewProducts($isNew, $perPage, $search = null)
    {
        $query = ProductsModel::with([
            'shop' => function ($queryShop) {
                $queryShop->select('shop_id', 'shop_name', 'shop_type');
            },
            'size' => function ($querySize) {
                $querySize->select('product_size_id', 'size_label');
            },
            'temperature' => function ($queryTemp) {
                $queryTemp->select('product_temp_id', 'temp_label');
            },
            'category' => function ($queryCategory) {
                $queryCategory->select('product_category_id', 'category_label', 'product_base_category_id');
            },
        ])
            ->where('availability_id', 1)
            ->when($isNew, function ($queryNew) use ($isNew) {
                $queryNew->where('is_new', $isNew);
            })
            ->when($perPage, function ($queryPaginate) use ($perPage) {
                $queryPaginate->paginate($perPage ?? 20);
            })
            ->when($search, function ($querySearch) use ($search) {
                $querySearch->where('product_name', 'like', '%' . $search . '%');
            })
            ->orderBy('product_name');

        return $query;
    }

    public function getAllCategoriesByNewProducts($isNew, $perPage, $search = null)
    {
        $query = CategoryModel::with([
            'baseCategory' => function ($querySelectMeal) {
                $querySelectMeal->select('meal_type');
            },
        ])
            ->whereHas('products', function ($queryNew) use ($isNew) {
                $queryNew->where('availability_id', 1)
                    ->when($isNew, function ($q) use ($isNew) {
                        $q->where('is_new', $isNew);
                    });
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

    public function getAllProductCategories($shopId, $branchId, $perPage, $search = null)
    {
        $query = CategoryModel::with([
            'baseCategory' => function ($querySelectMeal) {
                $querySelectMeal->select('meal_type');
            },
        ])
            ->when($shopId, function ($queryShop) use ($shopId) {
                $queryShop->where('shop_id', $shopId);
            })
            ->when($branchId, function ($queryBranch) use ($branchId) {
                $queryBranch->where('branch_id', $branchId);
            })
            ->when($perPage, function ($queryPaginate) use ($perPage) {
                $queryPaginate->paginate($perPage ?? 20);
            })
            ->when($search, function ($querySearch) use ($search) {
                $querySearch->where('product_name', 'like', '%' . $search . '%');
            })
            ->orderBy('category_label', 'asc');

        return $query;
    }
}

// This Repository is for Products and Categories module only
