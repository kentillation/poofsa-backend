<?php

namespace App\Repositories;

use App\Models\ProductsModel;
use App\Models\ProductsHistoryModel;

class ProductRepository
{
    public function getProducts($shopId, $branchId, $perPage, $search = null)
    {
        return ProductsModel::with([
            'temperature',
            'size',
            'category',
            'stations',
            'availability'
        ])
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->when($search, callback: function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('temperature', function ($q2) use ($search) {
                        $q2->where('temp_label', 'like', "%{$search}%");
                    })
                    ->orWhereHas('size', function ($q2) use ($search) {
                        $q2->where('size_label', 'like', "%{$search}%");
                    })
                    ->orWhereHas('category', function ($q2) use ($search) {
                        $q2->where('category_label', 'like', "%{$search}%");
                    })
                    ->orWhereHas('stations', function ($q2) use ($search) {
                        $q2->where('station_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('availability', function ($q2) use ($search) {
                        $q2->where('availability_label', 'like', "%{$search}%");
                    });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage ?? 10);
    }

    public function getProductsHistory($shopId, $branchId, $perPage, $search = null)
    {
        return ProductsHistoryModel::select(
            'tbl_products.product_name',
            'tbl_products_history.modified_type_id',
            'tbl_modified_type.modified_type',
            'tbl_products_history.description',
            'tbl_admin.admin_name',
            'tbl_products_history.updated_at',
        )
            ->join('tbl_modified_type', 'tbl_products_history.modified_type_id', '=', 'tbl_modified_type.modified_type_id')
            ->join('tbl_products', 'tbl_products_history.product_id', '=', 'tbl_products.product_id')
            ->join('tbl_admin', 'tbl_products_history.user_id', '=', 'tbl_admin.admin_id')
            ->where('tbl_products_history.shop_id', $shopId)
            ->where('tbl_products_history.branch_id', $branchId)
            ->when($search, callback: function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('tbl_admin.admin_name', 'like', "%{$search}%")
                    ->orWhere('tbl_products_history.description', 'like', "%{$search}%");
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage ?? 20);
    }
}

// This Repository is for Products module only
