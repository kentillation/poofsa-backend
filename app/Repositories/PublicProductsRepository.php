<?php

namespace App\Repositories;

use App\Models\ProductsModel;

class PublicProductsRepository
{
    public function getAllPublicProducts($shopId, $branchId, $search, $perPage)
    {
        $query = ProductsModel::with(['size', 'temperature', 'category'])
            ->where('availability_id', 1)
            ->when($shopId, function ($query) use ($shopId) {
                $query->where('shop_id', $shopId);
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('product_name', 'like', '%' . $search . '%');
            })
            ->orderBy('product_name');

        // Or if you want all results without pagination:
        return $query->get();

        // Return paginated results
        //return $query->paginate($perPage);
    }
}

// This Repository is for Products module only
