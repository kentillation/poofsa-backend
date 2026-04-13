<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\GetPublicProductsRequest;
use App\Http\Requests\GetPublicNewProductsRequest;
use App\Http\Requests\GetPublicByMealTypeRequest;
use App\Http\Requests\GetPublicShopsRequest;
use App\Actions\Products\GetPublicProductsAction;
use App\Actions\Products\GetPublicNewProductsAction;
use App\Actions\Products\GetPublicProductsByMealTypeAction;
use App\Actions\Products\GetPublicCategoriesByNewProductsAction;
use App\Actions\Products\GetPublicCategoriesByMealTypeAction;
use App\Actions\Products\GetPublicProductCategoriesAction;
use App\Actions\Shops\GetPublicShopsAction;
use App\Http\Resources\GetPublicProductsResource;
use App\Http\Resources\GetPublicNewProductsResource;
use App\Http\Resources\GetPublicProductsByMealTypeResource;
use App\Http\Resources\GetPublicCategoriesResource;
use App\Http\Resources\GetPublicShopsResource;
use App\Models\AdminModel;
use App\Models\ShopModel;
use App\Models\BranchModel;
use App\Models\ProductsModel;
use App\Models\CategoryModel;
use App\Models\ProductBaseCategoryModel;

class PublicController extends Controller
{

    // NEW STRUCTURED CODE
    public function getAllPublicShops(GetPublicShopsRequest $request, GetPublicShopsAction $action)
    {
        // execute if from GetPublicShopsAction
        $result = $action->execute(
            categoryLabel: $request->requested_category,
            mealType: $request->requested_meal_type,
            timeBetween: $request->requested_time_between,
            perPage: $request->items_per_page,
            search: $request->search,
        );

        return response()->json([
            'success' => true,
            'data' => GetPublicShopsResource::collection($result)
        ]);
    }

    public function getAllPublicProductsFromShop(GetPublicProductsRequest $request, GetPublicProductsAction $action)
    {
        $result = $action->execute(
            shopId: $request->shop_id,
            branchId: $request->branch_id,
            perPage: $request->items_per_page,
            search: $request->search,
        );

        return response()->json([
            'success' => true,
            'data' => GetPublicProductsResource::collection($result)
        ]);
    }

    public function getAllNewPublicProducts(GetPublicNewProductsRequest $request, GetPublicNewProductsAction $action)
    {
        $result = $action->execute(
            isNew: $request->is_new,
            perPage: $request->items_per_page,
            search: $request->search,
        );

        return response()->json([
            'success' => true,
            'data' => GetPublicNewProductsResource::collection($result)
        ]);
    }

    public function getAllPublicProductsByMealType(GetPublicByMealTypeRequest $request, GetPublicProductsByMealTypeAction $action)
    {
        $result = $action->execute(
            mealType: $request->meal_type,
            perPage: $request->items_per_page,
            search: $request->search
        );

        return response()->json([
            'success' => true,
            'data' => GetPublicProductsByMealTypeResource::collection($result)
        ]);
    }

    public function getAllCategoriesByNewProducts(GetPublicNewProductsRequest $request, GetPublicCategoriesByNewProductsAction $action)
    {
        $result = $action->execute(
            isNew: $request->is_new,
            perPage: $request->items_per_page,
            search: $request->search,
        );

        return response()->json([
            'success' => true,
            'data' => GetPublicCategoriesResource::collection($result)
        ]);
    }

    public function getAllCategoriesByMealType(GetPublicByMealTypeRequest $request, GetPublicCategoriesByMealTypeAction $action)
    {
        $result = $action->execute(
            mealType: $request->meal_type,
            perPage: $request->items_per_page,
            search: $request->search,
        );

        return response()->json([
            'success' => true,
            'data' => GetPublicCategoriesResource::collection($result)
        ]);
    }

    public function getAllProductCategories(GetPublicProductsRequest $request, GetPublicProductCategoriesAction $action)
    {
        $result = $action->execute(
            shopId: $request->shop_id,
            perPage: $request->items_per_page,
            search: $request->search,
        );

        return response()->json([
            'success' => true,
            'data' => GetPublicCategoriesResource::collection($result)
        ]);
    }
    // END OF NEW STRUCTURED CODE

    public function shopRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_name' => 'required|string|max:255',
            'shop_type' => 'required|string',
            'shop_owner' => 'required|string',
            'shop_address' => 'required|string',
            'shop_email' => 'required|email',
            'shop_contact_number' => 'required|string',
            'open_at' => 'required',
            'close_at' => 'required',
            'admin_password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {

            $shop = ShopModel::create([
                'shop_name' => $validated['shop_name'],
                'shop_type' => $validated['shop_type'],
                'shop_owner' => $validated['shop_owner'],
                'shop_address' => $validated['shop_address'],
                'shop_email' => $validated['shop_email'],
                'shop_contact_number' => $validated['shop_contact_number'],
                'open_at' => $validated['open_at'],
                'close_at' => $validated['close_at'],
            ]);
            $shopId = $shop->shop_id;

            BranchModel::create([
                'shop_id' => $shopId,
                'branch_name' => 'Main',
                'branch_address' => $validated['shop_address'],
                'branch_manager_name' => $validated['shop_owner'],
                'branch_contact_number' => $validated['shop_contact_number'],
            ]);

            $admin = AdminModel::create([
                'admin_name' => $validated['shop_owner'],
                'admin_email' => $validated['shop_email'],
                'admin_password' => Hash::make($validated['admin_password']),
                'shop_id' => $shopId,
            ]);

            Auth::guard('admin')->login($admin);

            $token = $admin->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;

            DB::commit();

            // Create cookie (similar to login response)
            $cookie = cookie(
                'XSRF-TOKEN',
                $token,
                config('session.lifetime'),
                '/',
                config('session.domain', null),
                config('session.secure', true),
                true,
                false,
                'Strict'
            );

            return response()->json([
                'message' => 'Registration successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 60 * 24 * 30, // 30 days
                'shop_id' => $admin->shop_id,
                'shop_name' => $shop->shop_name,
                'user_id' => $admin->admin_id,
            ])->withCookie($cookie);
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
                        'shop_address' => $shop->shop_address,
                        'open_at' => $shop->open_at,
                        'close_at' => $shop->close_at,
                        'lowest_price' => $lowestProduct->base_price,
                        'product_name' => $lowestProduct->product_name,
                        'product_id' => $lowestProduct->product_id,
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
                        'is_new' => $product->is_new,
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
                        'shop_type' => $product->shop->shop_type,
                        'shop_address' => $product->shop->shop_address,
                        'open_at' => $product->shop->open_at,
                        'close_at' => $product->shop->close_at,
                        'product_id' => $product->product_id,
                        'product_name' => $product->product_name,
                        'base_price' => $product->base_price,
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

    // Need pagination
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
                        'shop_address' => $product->shop->shop_address,
                        'open_at' => $product->shop->open_at,
                        'close_at' => $product->shop->close_at,
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
