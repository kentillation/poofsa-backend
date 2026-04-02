<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\GetPublicProductsRequest;
use App\Actions\Products\GetPublicProductsAction;
use App\Http\Resources\GetPublicProductsResource;
use App\Models\AdminModel;
use App\Models\CashierModel;
use App\Models\KitchenModel;
use App\Models\BaristaModel;
use App\Models\ShopModel;
use App\Models\BranchModel;
use App\Models\ProductsModel;
use App\Models\CategoryModel;
use App\Models\ProductBaseCategoryModel;

class PublicController extends Controller
{
    public function saveShop(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:50|unique:tbl_shops,shop_name',
            'shop_type' => 'required|string|max:50',
            'shop_owner' => 'required|string|max:50',
            'shop_address' => 'required|string',
            'shop_email' => 'required|string|email|max:191|unique:tbl_shops,shop_email',
            'shop_contact_number' => 'required|string|max:13',

            'branch_name' => 'required|string|max:50|unique:tbl_shop_branch,branch_name',
            'branch_address' => 'required|string',
            'branch_manager_name' => 'required|string|max:50',
            'branch_contact_number' => 'required|string|max:13',

            'admin_name' => 'required|string|max:191',
            'admin_email' => 'required|string|email|max:191|unique:tbl_admin,admin_email',
            'admin_password' => 'required|string|min:8',

            'cashier_name' => 'required|string|max:191',
            'cashier_email' => 'required|string|email|max:191|unique:tbl_cashier,cashier_email',
            'cashier_password' => 'required|string|min:8',

            'kitchen_personnel_name' => 'required|string|max:191',
            'kitchen_personnel_email' => 'required|string|email|max:191|unique:tbl_kitchen_personnel,kitchen_personnel_email',
            'kitchen_personnel_password' => 'required|string|min:8',

            'barista_name' => 'required|string|max:191',
            'barista_email' => 'required|string|email|max:191|unique:tbl_barista,barista_email',
            'barista_password' => 'required|string|min:8',
        ]);

        DB::beginTransaction();

        try {

            $shop = ShopModel::create([
                'shop_name' => $validated['shop_name'],
                'shop_owner' => $validated['shop_owner'],
                'shop_location' => $validated['shop_location'],
                'shop_email' => $validated['shop_email'],
                'shop_contact_number' => $validated['shop_contact_number'],
                'shop_status_id' => $validated['shop_status_id'],
            ]);
            $shopId = $shop->shop_id;

            $branch = BranchModel::create([
                'shop_id' => $shopId,
                'branch_name' => $validated['branch_name'],
                'branch_address' => $validated['branch_address'],
                'branch_manager_name' => $validated['branch_manager_name'],
                'branch_contact_number' => $validated['branch_contact_number'],
            ]);
            $branchId = $branch->branch_id;

            $admin = AdminModel::create([
                'admin_name' => $validated['admin_name'],
                'admin_email' => $validated['admin_email'],
                'admin_password' => Hash::make($validated['admin_password']),
                'shop_id' => $shopId,
            ]);

            $cashier = CashierModel::create([
                'cashier_name' => $validated['cashier_name'],
                'cashier_email' => $validated['cashier_email'],
                'cashier_password' => Hash::make($validated['cashier_password']),
                'shop_id' => $shopId,
                'branch_id' => $branchId,
            ]);

            $kitchen = KitchenModel::create([
                'kitchen_personnel_name' => $validated['kitchen_personnel_name'],
                'kitchen_personnel_email' => $validated['kitchen_personnel_email'],
                'kitchen_personnel_password' => Hash::make($validated['kitchen_personnel_password']),
                'shop_id' => $shopId,
                'branch_id' => $branchId,
            ]);

            $barista = BaristaModel::create([
                'barista_name' => $validated['barista_name'],
                'barista_email' => $validated['barista_email'],
                'barista_password' => Hash::make($validated['barista_password']),
                'shop_id' => $shopId,
                'branch_id' => $branchId,
            ]);

            $token = $shop->createToken('auth-token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Registration successful',
                'shop' => $shop,
                'branch' => $branch,
                'admin' => $admin->makeHidden(['admin_password', 'admin_mpin']),
                'cashier' => $cashier->makeHidden(['cashier_password']),
                'kitchen' => $kitchen->makeHidden(['kitchen_personnel_password']),
                'barista' => $barista->makeHidden(['barista_password']),
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Account registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getShops(Request $request)
    {
        try {
            $requestedCategory = $request->input('requested_category');
            $requestedMealType = $request->input('requested_meal_type');
            $requestedTimeBetween = $request->input('requested_time_between'); // between 'open_at' and 'close_at'

            $query = ShopModel::query();

            $query->whereHas('products', function ($q) use ($requestedCategory, $requestedMealType, $requestedTimeBetween) {
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

                if ($requestedTimeBetween) {
                    $q->whereHas('shop', function ($shopQuery) use ($requestedTimeBetween) {
                        $shopQuery->whereTime('open_at', '<=', $requestedTimeBetween)
                            ->whereTime('close_at', '>=', $requestedTimeBetween);
                    });
                }
            });

            $shops = $query->with(['products' => function ($q) use ($requestedCategory, $requestedMealType, $requestedTimeBetween) {
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
                $branchId = $shop->products->first()->branch_id ?? null;

                if ($lowestProduct) {
                    return [
                        'shop_id' => $shop->shop_id,
                        'branch_id' => $branchId,
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

    // New Structured Code for Get All Public Products with Pagination and Search
    public function getAllPublicProducts(GetPublicProductsRequest $request, GetPublicProductsAction $action)
    {
        $result = $action->execute(
            shopId: $request->shop_id,
            branchId: $request->branch_id,
            search: $request->search,
            perPage: $request->itemsPerPage ?? 10
        );

        return response()->json([
            'success' => true,
            'data' => GetPublicProductsResource::collection($result)
        ]);
    }

    public function getNewProducts(Request $request)
    {
        try {
            $isNew = $request->is_new;

            $data = ProductsModel::with(['shop', 'size', 'temperature', 'category'])
                ->where('availability_id', 1)
                ->when($isNew, function ($query) use ($isNew) {
                    $query->where('is_new', $isNew);
                })
                ->orderBy('product_name')
                ->get()
                ->map(function ($product) {
                    return [
                        'branch_id' => $product->branch_id,
                        'shop_id' => $product->shop->shop_id,
                        'shop_name' => $product->shop->shop_name,
                        'product_id' => $product->product_id,
                        'product_name' => $product->product_name,
                        'base_price' => $product->base_price,
                        'availability_id' => $product->availability_id,
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

    public function getCategoriesByNewProducts(Request $request)
    {
        try {
            $isNew = $request->is_new;

            $data = CategoryModel::with('baseCategory')
                ->whereHas('products', function ($query) use ($isNew) {
                    $query->where('availability_id', 1)
                        ->when($isNew, function ($q) use ($isNew) {
                            $q->where('is_new', $isNew);
                        });
                })
                ->orderBy('category_label', 'asc')
                ->get()
                ->map(function ($data) {
                    return [
                        'shop_id' => $data->shop_id,
                        'product_category_id' => $data->product_category_id,
                        'category_label' => $data->category_label,
                        'meal_type' => $data->baseCategory->meal_type ?? null,
                        'product_base_category_id' => $data->product_base_category_id,
                    ];
                })
                ->unique('category_label')
                ->values();

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
                        'shop_type' => $product->shop->shop_type,
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
