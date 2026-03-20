<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ProductsModel;
use App\Models\CategoryModel;
use App\Models\ProductBaseCategoryModel;

class PublicController extends Controller
{
    public function getShops(Request $request)
    {
        try {
            $requestedCategory = $request->requested_category;

            $sub = DB::table('tbl_products')
                ->select(
                    'tbl_products.shop_id',
                    'tbl_products.base_price',
                    'tbl_products.product_name',
                    'tbl_product_category.category_label',
                    DB::raw('ROW_NUMBER() OVER (
                        PARTITION BY tbl_products.shop_id 
                        ORDER BY tbl_products.base_price ASC, tbl_products.product_id ASC
                    ) as rn')
                )
                ->join(
                    'tbl_product_category',
                    'tbl_products.category_id',
                    '=',
                    'tbl_product_category.product_category_id'
                )
                // FILTER HERE
                ->when($requestedCategory, function ($query) use ($requestedCategory) {
                    $query->where('tbl_product_category.category_label', $requestedCategory);
                });

            $data = DB::table('tbl_shops')
                ->select(
                    'tbl_shops.shop_id',
                    'tbl_shops.shop_name',
                    'tbl_shops.shop_type',
                    'p.base_price as lowest_price',
                    'p.product_name',
                )
                ->joinSub($sub, 'p', function ($join) {
                    $join->on('tbl_shops.shop_id', '=', 'p.shop_id')
                        ->where('p.rn', 1); // still ensures ONE per shop
                })
                ->get();

            return response()->json([
                'success' => true,
                'message' => $data->isEmpty() ? 'No shop found!' : 'Shops fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shops!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getProducts(Request $request)
    {
        try {
            $requestedCategory = $request->requested_category;

            $data = ProductsModel::select(
                'tbl_products.branch_id',
                'tbl_products.shop_id',
                'tbl_products.product_id',
                'tbl_products.product_name',
                'tbl_products.base_price',
                'tbl_products.availability_id',
                'tbl_products.station_id',
                'tbl_product_temp.temp_label',
                'tbl_product_size.size_label',
                'tbl_product_category.category_label',
            )
                ->join('tbl_product_temp', 'tbl_products.temp_id', '=', 'tbl_product_temp.product_temp_id')
                ->join('tbl_product_size', 'tbl_products.size_id', '=', 'tbl_product_size.product_size_id')
                ->join('tbl_product_category', 'tbl_products.category_id', '=', 'tbl_product_category.product_category_id')
                ->where('tbl_products.availability_id', 1)

                // FILTER HERE
                ->when($requestedCategory, function ($query) use ($requestedCategory) {
                    $query->where('tbl_product_category.category_label', $requestedCategory);
                })

                ->orderBy('tbl_products.product_name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => $data->isEmpty() ? 'No products found!' : 'Products fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductBaseCategories()
    {
        try {
            $data = ProductBaseCategoryModel::orderBy('product_base_category_id', 'asc')
            ->limit(10)
            ->get();
            return response()->json([
                'success' => true,
                'message' => $data->isEmpty() ? 'No product base category found!' : 'Product base categories fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product base categories!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductCategories()
    {
        try {
            $data = CategoryModel::orderBy('category_label', 'asc')->get();
            return response()->json([
                'success' => true,
                'message' => $data->isEmpty() ? 'No category found!' : 'Categories fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching categories!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
