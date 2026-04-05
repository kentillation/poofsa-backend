<?php

namespace App\Repositories;

use App\Models\ProductsModel;

class PublicNewProductsRepository
{
    public function getAllPublicNewProducts($isNew, $perPage = 20, $search = null)
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
        ])
            ->where('availability_id', 1)
            ->when($isNew, function ($query) use ($isNew) {
                $query->where('is_new', $isNew);
            })
            ->when($perPage, function ($query) use ($perPage) {
                $query->paginate($perPage);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('product_name', 'like', '%' . $search . '%');
            })
            ->orderBy('product_name');

        return $query->paginate($perPage ?? 20);
    }
}

// This Repository is for Products module only
