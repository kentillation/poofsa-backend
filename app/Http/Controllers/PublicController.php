<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendRecoveryCode;
use App\Mail\SendSuccessMessage;
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
        $validator = Validator::make(
            $request->all(),
            [
                'shop_name' => [
                    'required',
                    'string',
                    'max:30',
                    function ($attribute, $value, $fail) {
                        $exists = ShopModel::whereRaw('LOWER(shop_name) = ?', [strtolower($value)])->orWhereRaw(
                            'LOWER(shop_name) LIKE ?',
                            [strtolower($value) . '%']
                        )->exists();
                        if ($exists) {
                            $fail('Shop name already taken or is too similar.');
                        }
                    }
                ],
                'shop_type' => 'required|string',
                'shop_owner' => 'required|string',
                'shop_address' => 'required|string',
                'shop_email' => 'required|email|unique:tbl_shops,shop_email',
                'shop_contact_number' => 'required|string|unique:tbl_shops,shop_contact_number',
                'open_at' => 'required',
                'close_at' => 'required',
                'admin_password' => 'required|min:6',
            ],
            [
                'shop_email.unique' => 'Email address already taken.',
                'shop_contact_number.unique' => 'Mobile number already taken.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {

            $isOvernight = $validated['close_at'] < $validated['open_at'] ? 1 : 0;
            $shop = ShopModel::create([
                'shop_name' => $validated['shop_name'],
                'shop_type' => $validated['shop_type'],
                'shop_owner' => $validated['shop_owner'],
                'shop_address' => $validated['shop_address'],
                'shop_email' => $validated['shop_email'],
                'shop_contact_number' => $validated['shop_contact_number'],
                'open_at' => $validated['open_at'],
                'close_at' => $validated['close_at'],
                'is_overnight' => $isOvernight,
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

    public function verifyEmail(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'admin_email' => 'required|email'
            ]);

            $email = $validated['admin_email'];

            // Check if email exists
            $admin = AdminModel::where('admin_email', $email)->first();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found. Please try again!'
                ], 404);
            }

            // Generate recovery code
            $recoveryCode = rand(100000, 999999);

            // Save recovery code (IMPORTANT)
            $admin->recovery_code = $recoveryCode;
            $admin->recovery_code_expires_at = now()->addMinutes(10); // optional
            $admin->save();

            // Send email
            try {
                Mail::to($email)->send(new SendRecoveryCode($recoveryCode));
            } catch (\Exception $e) {
                Log::error('Mail error: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Recovery code sent successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'admin_email' => 'required|email',
            'recovery_code' => 'required'
        ]);

        $admin = AdminModel::where('admin_email', $request->admin_email)
            ->where('recovery_code', $request->recovery_code)
            ->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid recovery code. Try again!'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recovery code has been verified.'
        ]);
    }

    public function recoverAccount(Request $request)
    {
        $validated = $request->validate([
            'admin_email' => 'required|email',
            'recovery_code' => 'required',
            'new_password' => 'required|string|min:8',
        ]);

        DB::beginTransaction();

        try {
            $admin = AdminModel::where('admin_email', $validated['admin_email'])->first();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found.'
                ], 404);
            }

            // Validate recovery code
            if (!$admin->recovery_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recovery code already used. Please request a new one.'
                ], 400);
            }

            // Check expiration
            if (!$admin->recovery_code_expires_at || now()->gt($admin->recovery_code_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recovery code expired.'
                ], 400);
            }

            // Max attempts
            $MAX_ATTEMPTS = 3;

            // Already used
            // if ($admin->recovery_code_used_at) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Recovery code already used. Please try again.'
            //     ], 400);
            // }

            // Too many attempts
            if ($admin->recovery_attempts >= $MAX_ATTEMPTS) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many attempts. Please request a new recovery code.'
                ], 429);
            }

            // Invalid code
            if ($admin->recovery_code !== $validated['recovery_code']) {

                // Increment attempts
                $admin->increment('recovery_attempts');

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid recovery code.'
                ], 400);
            }

            // Expired
            if (!$admin->recovery_code_expires_at || now()->gt($admin->recovery_code_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recovery code expired.'
                ], 400);
            }

            // Update password
            $admin->admin_password = Hash::make($validated['new_password']);
            $admin->recovery_code = null;
            $admin->recovery_code_expires_at = null;
            $admin->recovery_code_used_at = now();
            $admin->recovery_attempts = 0;
            $admin->save();

            $shop = ShopModel::where('shop_id', $admin->shop_id)->first();

            // Send email (non-blocking)
            try {
                Mail::to($admin->admin_email)->send(new SendSuccessMessage());
            } catch (\Exception $e) {
                Log::error('Mail error: ' . $e->getMessage());
            }

            Auth::guard('admin')->login($admin);

            $token = $admin->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;

            DB::commit();

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
                'message' => "You've successfully reset your password",
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 60 * 24 * 30, // 30 days
                'shop_id' => $admin->shop_id,
                'shop_name' => $shop->shop_name,
                'user_id' => $admin->admin_id,
            ])->withCookie($cookie);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Server error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error. Try again.'
            ], 500);
        }
    }

    public function getShopsWithoutProducts(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10); // Default to 10 items per page

            // Query shops that have NO products
            $shops = ShopModel::query()
                ->whereDoesntHave('products')
                ->paginate($perPage, ['shop_id', 'branch_id', 'shop_name', 'shop_type']);

            return response()->json([
                'success' => true,
                'message' => $shops->isEmpty() ? 'No shops without products found!' : 'Shops fetched successfully!',
                'data' => $shops->items(),
                'pagination' => [
                    'current_page' => $shops->currentPage(),
                    'last_page' => $shops->lastPage(),
                    'per_page' => $shops->perPage(),
                    'total' => $shops->total(),
                    'next_page_url' => $shops->nextPageUrl(),
                    'prev_page_url' => $shops->previousPageUrl(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shops!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getShops(Request $request)
    {
        try {
            $requestedCategory = $request->input('requested_category');
            $requestedMealType = $request->input('requested_meal_type');
            $requestedTimeBetween = $request->input('requested_time_between');

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
                        $shopQuery->where(function ($query) use ($requestedTimeBetween) {

                            // Case 1: 24 hours (open_at == close_at)
                            $query->where(function ($q0) {
                                $q0->whereColumn('open_at', '=', 'close_at');
                            })

                                // Case 2: Normal hours
                                ->orWhere(function ($q1) use ($requestedTimeBetween) {
                                    $q1->where('is_overnight', 0)
                                        ->whereTime('open_at', '<=', $requestedTimeBetween)
                                        ->whereTime('close_at', '>=', $requestedTimeBetween);
                                })

                                // Case 3: Overnight
                                ->orWhere(function ($q2) use ($requestedTimeBetween) {
                                    $q2->where('is_overnight', 1)
                                        ->where(function ($q3) use ($requestedTimeBetween) {
                                            $q3->whereTime('open_at', '<=', $requestedTimeBetween)
                                                ->orWhereTime('close_at', '>=', $requestedTimeBetween);
                                        });
                                });
                        });
                    });
                }
            });

            // Add pagination here - paginate 10 items per page
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
            }])->paginate(10); // Changed from get() to paginate(10)

            // Transform the paginated data
            $filteredShops = collect($shops->items())->map(function ($shop) {
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
                'data' => $filteredShops,
                'pagination' => [
                    'current_page' => $shops->currentPage(),
                    'last_page' => $shops->lastPage(),
                    'per_page' => $shops->perPage(),
                    'total' => $shops->total(),
                    'next_page_url' => $shops->nextPageUrl(),
                    'prev_page_url' => $shops->previousPageUrl(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shops!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* public function getShops(Request $request)
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
    }*/


    /* public function getProducts(Request $request)
    {
        try {
            $shopId = $request->shop_id;
            $branchId = $request->branch_id;

            $data = ProductsModel::with(['size', 'temperature', 'category'])
                ->where('availability_id', 1)
                ->when($shopId, function ($query) use ($shopId) {
                    $query->where('shop_id', $shopId);
                })
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
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
    } */

    public function getProducts(Request $request)
    {
        try {
            $shopId = $request->shop_id;
            $branchId = $request->branch_id;
            $itemsPerPage = $request->items_per_page ?? 20;

            $products = ProductsModel::with(['size', 'temperature', 'category'])
                ->where('availability_id', 1)
                ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->orderBy('product_name')
                ->paginate($itemsPerPage);

            // Transform only items
            $products->getCollection()->transform(function ($product) {
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
                'message' => $products->isEmpty()
                    ? 'No products found!'
                    : 'Products fetched successfully!',
                'data' => $products->items(),

                // ⭐ IMPORTANT FOR INFINITE SCROLL
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
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
