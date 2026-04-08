<?php

namespace App\Repositories;

use App\Models\ProductsModel;

class PublicProductsRepository
{
    public function getAllPublicProducts($shopId, $branchId, $perPage, $search = null)
    {
        $perPage = (int) $perPage ?: 20;
        
        $query = ProductsModel::with(['size', 'temperature', 'category'])
            ->where('availability_id', 1)
            ->when($shopId, function ($queryShop) use ($shopId) {
                $queryShop->where('shop_id', $shopId);
            })
            ->when($branchId, function ($queryBranch) use ($branchId) {
                $queryBranch->where('branch_id', $branchId);
            })
            ->when($search, function ($querySearch) use ($search) {
                $querySearch->where('product_name', 'like', '%' . $search . '%');
            })
            ->orderBy('product_name');

        return $query->paginate($perPage ?? 20);
    }
}

// This Repository is for Products module only
