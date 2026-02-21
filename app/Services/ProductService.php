<?php

namespace App\Services;

use App\Models\ProductsModel;
use App\Models\ProductsHistoryModel;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Get all Products
     */
    public static function getProductsService($shopId, $branchId)
    {
        $products = ProductsModel::select(
            'tbl_products.product_id',
            'tbl_products.product_name',
            'tbl_products.base_price',
            'tbl_products.cost_estimate',
            'tbl_products.temp_id',
            'tbl_products.size_id',
            'tbl_products.updated_at',
            'tbl_products.category_id',
            'tbl_products.availability_id',
            'tbl_products.station_id',
            'tbl_product_temp.temp_label',
            'tbl_product_size.size_label',
            'tbl_product_category.category_label',
            'tbl_availability.availability_label',
            'tbl_products.branch_id',
            'tbl_products.shop_id',
        )
            ->join('tbl_product_temp', 'tbl_products.temp_id', '=', 'tbl_product_temp.product_temp_id')
            ->join('tbl_product_size', 'tbl_products.size_id', '=', 'tbl_product_size.product_size_id')
            ->join('tbl_product_category', 'tbl_products.category_id', '=', 'tbl_product_category.product_category_id')
            ->join('tbl_availability', 'tbl_products.availability_id', '=', 'tbl_availability.availability_id')
            ->where('tbl_products.shop_id', $shopId)
            ->where('tbl_products.branch_id', $branchId)
            ->orderByDesc('tbl_products.updated_at')
            ->get();

        return $products;
    }

    public static function getProductsHistoryService($shopId, $branchId)
    {
        $products = ProductsHistoryModel::select(
            'tbl_products.product_name',
            'tbl_products_history.manage_id',
            'tbl_products_history.description',
            'tbl_admin.admin_name',
            'tbl_products_history.updated_at',
        )
            ->join('tbl_products', 'tbl_products_history.product_id', '=', 'tbl_products.product_id')
            ->join('tbl_admin', 'tbl_products_history.user_id', '=', 'tbl_admin.admin_id')
            ->where('tbl_products_history.shop_id', $shopId)
            ->where('tbl_products_history.branch_id', $branchId)
            ->orderBy('tbl_products_history.updated_at')
            ->get();

        return $products;
    }

    public static function getTotalProductsCountService($shopId, $branchId)
    {
        $totalProducts = ProductsModel::select(
            DB::raw('COUNT(tbl_products.product_id) as total_products')
        )
            ->where('tbl_products.shop_id', $shopId)
            ->where('tbl_products.branch_id', $branchId)
            ->first();

        return $totalProducts;
    }
}
