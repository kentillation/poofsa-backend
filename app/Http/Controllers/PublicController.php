<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ShopModel;
use App\Models\ProductsModel;
use App\Models\CategoryModel;
use App\Models\ProductBaseCategoryModel;

class PublicController extends Controller
{
    public function getShops(Request $request)
    {
        try {
            $requestedCategory = $request->input('requested_category');
            $requestedMealType = $request->input('requested_meal_type');

            $query = ShopModel::query();

            $query->whereHas('products', function ($q) use ($requestedCategory, $requestedMealType) {
                $q->where('availability_id', 1);

                if ($requestedCategory) {
                    $q->whereHas('category', function ($cat) use ($requestedCategory) {
                        $cat->where('category_label', $requestedCategory);
                    });
                }

                if ($requestedMealType) {
                    $q->whereHas('category.baseCategory', function ($base) use ($requestedMealType) {
                        $base->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($requestedMealType)]);
                    });
                }
            });

            $shops = $query->with(['products' => function ($q) use ($requestedCategory, $requestedMealType) {
                $q->where('availability_id', 1);

                if ($requestedCategory) {
                    $q->whereHas('category', function ($cat) use ($requestedCategory) {
                        $cat->where('category_label', $requestedCategory);
                    });
                }

                if ($requestedMealType) {
                    $q->whereHas('category.baseCategory', function ($base) use ($requestedMealType) {
                        $base->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($requestedMealType)]);
                    });
                }
            }])->get();

            $filteredShops = $shops->map(function ($shop) {
                $lowestProduct = $shop->products->sortBy('base_price')->first();

                if ($lowestProduct) {
                    return [
                        'shop_id' => $shop->shop_id,
                        'shop_name' => $shop->shop_name,
                        'shop_type' => $shop->shop_type,
                        'lowest_price' => $lowestProduct->base_price,
                        'product_name' => $lowestProduct->product_name,
                        'category_label' => $lowestProduct->category->category_label ?? null,
                    ];
                }

                return null;
            })->filter()->values();

            return response()->json([
                'success' => true,
                'message' => $filteredShops->isEmpty() ? 'No shop found!' : 'Shops fetched successfully!',
                'data' => $filteredShops
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
            $shopId = $request->shop_id;

            $data = ProductsModel::with(['size', 'temperature', 'category'])
                ->where('availability_id', 1)
                ->when($shopId, function ($query) use ($shopId) {
                    $query->where('shop_id', $shopId);
                })
                ->orderBy('product_name')
                ->get()
                ->map(function ($product) {
                    return [
                        'branch_id' => $product->branch_id,
                        'shop_id' => $product->shop_id,
                        'product_id' => $product->product_id,
                        'product_name' => $product->product_name,
                        'base_price' => $product->base_price,
                        'availability_id' => $product->availability_id,
                        'station_id' => $product->station_id,
                        'temp_label' => $product->temperature->temp_label ?? null,
                        'size_label' => $product->size->size_label ?? null,
                        'category_label' => $product->category->category_label ?? null,
                    ];
                });

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
    public function getProductsByMealType(Request $request)
    {
        try {
            $mealType = $request->meal_type;

            if (!$mealType) {
                return response()->json([
                    'success' => false,
                    'message' => 'meal_type is required'
                ], 400);
            }

            $data = ProductsModel::with(['shop', 'size', 'temperature', 'category', 'category.baseCategory'])
                ->where('availability_id', 1)
                ->whereHas('category.baseCategory', function ($query) use ($mealType) {
                    $query->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
                })
                ->orderBy('product_name')
                ->get()
                ->map(function ($product) {
                    return [
                        'branch_id' => $product->branch_id,
                        'shop_id' => $product->shop_id,
                        'shop_name' => $product->shop->shop_name,
                        'product_id' => $product->product_id,
                        'product_name' => $product->product_name,
                        'base_price' => $product->base_price,
                        'availability_id' => $product->availability_id,
                        'station_id' => $product->station_id,
                        'temp_label' => $product->temperature->temp_label ?? null,
                        'size_label' => $product->size->size_label ?? null,
                        'category_label' => $product->category->category_label ?? null,
                    ];
                });

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
    public function getProductCategories(Request $request)
    {
        try {
            $shopId = $request->shop_id;
            $data = CategoryModel::with('baseCategory')
                ->when($shopId, function ($query) use ($shopId) {
                    $query->where('shop_id', $shopId);
                })
                ->orderBy('category_label', 'asc')
                ->get()
                ->map(function ($data) {
                    return [
                        'shop_id' => $data->shop_id,
                        'product_category_id' => $data->product_category_id,
                        'category_label' => $data->category_label,
                        'meal_type' => $data->baseCategory->meal_type,
                        'product_base_category_id' => $data->product_base_category_id,
                    ];
                });
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
    public function getCategoriesByMealType(Request $request)
    {
        try {
            $mealType = $request->meal_type;

            if (!$mealType) {
                return response()->json([
                    'success' => false,
                    'message' => 'meal_type is required'
                ], 400);
            }

            $data = CategoryModel::with('baseCategory')
                ->whereHas('baseCategory', function ($query) use ($mealType) {
                    $query->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
                })
                ->orderBy('category_label', 'asc')
                ->get()
                ->map(function ($data) {
                    return [
                        'shop_id' => $data->shop_id,
                        'product_category_id' => $data->product_category_id,
                        'category_label' => $data->category_label,
                        'meal_type' => $data->baseCategory->meal_type,
                        'product_base_category_id' => $data->product_base_category_id,
                    ];
                })
                ->unique('category_label')  // This removes duplicates by category_label
                ->values();  // This resets the keys

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
    public function getProductBaseCategories()
    {
        try {
            $data = ProductBaseCategoryModel::orderBy('product_base_category_id', 'asc')->get();
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
}
