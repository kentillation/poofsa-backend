<?php

namespace App\Repositories;

use App\Models\ProductsModel;

class PublicProductsRepository
{
    public function getAllPublicProducts($shopId, $branchId, $perPage, $search = null)
    {
        $query = ProductsModel::with(['size', 'temperature', 'category'])
            ->where('availability_id', 1)
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
            ->orderBy('product_name');

        return $query;
    }
}

// This Repository is for Products module only
