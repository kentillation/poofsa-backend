<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\SendRecoveryCode;
use App\Models\CustomerModel;
use App\Models\ShopModel;
use App\Models\BranchModel;
use App\Models\ProductsModel;
use App\Models\CategoryModel;
use App\Models\ProductBaseCategoryModel;

class CustomerController extends Controller
{
    protected function getTokenExpiration($remember = false)
    {
        return $remember ? now()->addDays(30) : now()->addDays(7);
    }

    public function customerRegistration(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => 'required|string|max:50',
                'middle_name' => 'nullable|string|max:50',
                'last_name' => 'required|string|max:50',
                'pet_name' => 'nullable|string|max:50',
                'customer_contact_number' => 'required|string|max:13|unique:tbl_customers,customer_contact_number',
                'customer_email' => 'required|email|max:50|unique:tbl_customers,customer_email',
                'customer_password' => 'required|min:8',
            ],
            [
                'customer_email.unique' => 'Email address already taken.',
                'customer_contact_number.unique' => 'Mobile number already taken.',
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

            $customer = CustomerModel::create([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'pet_name' => $validated['pet_name'],
                'customer_contact_number' => $validated['customer_contact_number'],
                'customer_email' => $validated['customer_email'],
                'customer_password' => Hash::make($validated['customer_password']),
            ]);

            $remember = $request->boolean('remember');
            $token = $customer->createToken('auth_token', ['customer:access'], $this->getTokenExpiration($remember))->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "You’ve successfully registered",
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 60 * 24 * 30,
                'user_id' => $customer->customer_id,
                'first_name' => $customer->first_name,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
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
                'customer_email' => 'required|email'
            ]);

            $email = $validated['customer_email'];

            // Check if email exists
            $customer = CustomerModel::where('customer_email', $email)->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found. Please try again!'
                ], 404);
            }

            // Generate recovery code
            $recoveryCode = rand(100000, 999999);

            // Save recovery code (IMPORTANT)
            $customer->recovery_code = $recoveryCode;
            $customer->recovery_code_expires_at = now()->addMinutes(10); // optional
            $customer->save();

            // Send email
            try {
                Mail::to($email)->send(new SendRecoveryCode($recoveryCode));
            } catch (\Exception $e) {
                Log::error('Mail error: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email.',
                    'error' => $e->getMessage()
                ], 500);
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

    public function getShops(Request $request)
    {
        try {
            $requestedCategory = $request->input('requested_category');
            $requestedMealType = $request->input('requested_meal_type');
            $requestedTimeBetween = $request->input('requested_time_between');
            $perPage = $request->input('per_page', 20);

            $query = ShopModel::query();

            if ($requestedCategory || $requestedMealType || $requestedTimeBetween) {
                $query->whereHas('branches.products', function ($q) use ($requestedCategory, $requestedMealType, $requestedTimeBetween) {
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
                        $q->whereHas('branch', function ($branchQuery) use ($requestedTimeBetween) {
                            $branchQuery->where(function ($query) use ($requestedTimeBetween) {
                                $query->where(function ($q0) {
                                    $q0->whereColumn('open_at', '=', 'close_at');
                                })
                                    ->orWhere(function ($q1) use ($requestedTimeBetween) {
                                        $q1->where('is_overnight', 0)
                                            ->whereTime('open_at', '<=', $requestedTimeBetween)
                                            ->whereTime('close_at', '>=', $requestedTimeBetween);
                                    })
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
            }

            $query->with(['branches' => function ($branchQuery) use ($requestedCategory, $requestedMealType, $requestedTimeBetween) {
                if ($requestedTimeBetween) {
                    $branchQuery->where(function ($query) use ($requestedTimeBetween) {
                        $query->where(function ($q0) {
                            $q0->whereColumn('open_at', '=', 'close_at');
                        })
                        ->orWhere(function ($q1) use ($requestedTimeBetween) {
                            $q1->where('is_overnight', 0)
                            ->whereTime('open_at', '<=', $requestedTimeBetween)
                            ->whereTime('close_at', '>=', $requestedTimeBetween);
                        })
                        ->orWhere(function ($q2) use ($requestedTimeBetween) {
                            $q2->where('is_overnight', 1)
                            ->where(function ($q3) use ($requestedTimeBetween) {
                                $q3->whereTime('open_at', '<=', $requestedTimeBetween)
                                    ->orWhereTime('close_at', '>=', $requestedTimeBetween);
                            });
                        });
                    });
                }

                $branchQuery->with(['products' => function ($productQuery) use ($requestedCategory, $requestedMealType) {
                    $productQuery->where('availability_id', 1)
                        ->select('product_id', 'branch_id', 'base_price', 'product_name', 'category_id');
                    if ($requestedCategory) {
                        $productQuery->whereHas('category', function ($cat) use ($requestedCategory) {
                            $cat->where('category_label', $requestedCategory);
                        });
                    }
                    if ($requestedMealType) {
                        $productQuery->whereHas('category.baseCategory', function ($base) use ($requestedMealType) {
                            $base->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($requestedMealType)]);
                        });
                    }
                }]);
            }]);

            $shops = $query->paginate($perPage);

            // Transform data
            $filteredShops = collect($shops->items())->map(function ($shop) {
                $allProducts = $shop->branches->flatMap(function ($branch) {
                    return $branch->products;
                });

                $lowestProduct = $allProducts->sortBy('base_price')->first();

                // Determine branch_id based on whether shop has products
                $branchId = null;
                if ($lowestProduct) {
                    // Has products - use branch_id from the lowest price product
                    $branchId = $lowestProduct->branch_id;
                } elseif ($shop->branches->isNotEmpty()) {
                    // No products but has branches - use the first branch's ID
                    $branchId = $shop->branches->first()->branch_id;
                }

                $selectedBranch = $shop->branches->firstWhere('branch_id', $branchId);

                return [
                    'shop_id' => $shop->shop_id,
                    'branch_id' => $branchId,
                    'shop_name' => $shop->shop_name,
                    'shop_type' => $shop->shop_type,
                    'shop_address' => optional($selectedBranch)->branch_address,
                    'open_at' => optional($selectedBranch)->open_at,
                    'close_at' => optional($selectedBranch)->close_at,
                    'has_products' => $allProducts->isNotEmpty(),
                    'has_branches' => $shop->branches->isNotEmpty(),
                    'lowest_price' => $lowestProduct->base_price ?? null,
                    'product_name' => $lowestProduct->product_name ?? null,
                    'product_id' => $lowestProduct->product_id ?? null,
                    'category_label' => $lowestProduct->category->category_label ?? null,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => $filteredShops->isEmpty() ? 'No shops found!' : 'Shops fetched successfully!',
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
                    $q->whereHas('branches', function ($shopQuery) use ($requestedTimeBetween) {
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

    public function getShopLocation(Request $request)
    {
        try {
            $shopId = $request->shop_id;
            $branchId = $request->branch_id;

            $shop = ShopModel::find($shopId);

            $branch = BranchModel::where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->get()
                ->map(function ($product) use ($shop) {
                    return [
                        'thumbnail_url' => $shop->thumbnail_url,
                        'branch_name' => $product->branch_name,
                        'branch_address' => $product->branch_address,
                        'branch_latitude' => $product->branch_latitude,
                        'branch_longitude' => $product->branch_longitude,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => $branch->isEmpty() ? 'No location found!' : 'Location fetched successfully!',
                'data' => $branch
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching location!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProducts(Request $request)
    {
        try {
            $shopId = $request->shop_id;
            $branchId = $request->branch_id;
            $categoryLabel = $request->category_label;
            $itemsPerPage = $request->items_per_page ?? 20;

            $products = ProductsModel::with(['size', 'temperature', 'category'])
                ->where('availability_id', 1)
                ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($categoryLabel, function ($q) use ($categoryLabel) {
                    $q->whereHas('category', function ($query) use ($categoryLabel) {
                        $query->where('category_label', $categoryLabel);
                    });
                })
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
                    'thumbnail_url' => $product->thumbnail_url,
                    'standard_image_url' => $product->standard_image_url,
                    'image_size_kb' => $product->image_size_kb,
                    'has_image' => $product->has_image,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $products->isEmpty()
                    ? 'No products found!'
                    : 'Products fetched successfully!',
                'data' => $products->items(),

                // IMPORTANT FOR INFINITE SCROLL
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
    }*/

    public function getNewProducts(Request $request)
    {
        try {
            $isNew = $request->is_new;
            $itemsPerPage = $request->items_per_page ?? 20;

            $products = ProductsModel::with(['shop', 'branch', 'size', 'temperature', 'category'])
                ->where('availability_id', 1)
                ->when($isNew, function ($query) use ($isNew) {
                    $query->where('is_new', $isNew);
                })
                ->orderBy('product_name')
                ->paginate($itemsPerPage);

            // Transform only items
            $products->getCollection()->transform(function ($product) {
                return [
                    'branch_id' => $product->branch_id,
                    'shop_id' => $product->shop->shop_id,
                    'shop_name' => $product->shop->shop_name,
                    'shop_type' => $product->shop->shop_type,
                    'shop_address' => $product->branch->branch_address,
                    'open_at' => $product->branch->open_at,
                    'close_at' => $product->branch->close_at,
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
                'message' => $products->isEmpty()
                    ? 'No products found!'
                    : 'Products fetched successfully!',
                'data' => $products->items(),

                // IMPORTANT FOR INFINITE SCROLL
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
            $itemsPerPage = $request->items_per_page ?? 20;

            if (!$mealType) {
                return response()->json([
                    'success' => false,
                    'message' => 'meal_type is required'
                ], 400);
            }

            $products = ProductsModel::with(['shop', 'branch', 'size', 'temperature', 'category', 'category.baseCategory'])
                ->where('availability_id', 1)
                ->whereHas('category.baseCategory', function ($query) use ($mealType) {
                    $query->whereJsonContains('meal_type', $mealType);
                })
                ->orderBy('product_name')
                ->paginate($itemsPerPage);

            // Transform only items
            $products->getCollection()->transform(function ($product) {
                return [
                    'shop_name' => $product->shop->shop_name,
                    'shop_type' => $product->shop->shop_type,
                    'shop_address' => $product->branch->branch_address,
                    'open_at' => $product->branch->open_at,
                    'close_at' => $product->branch->close_at,
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
                    $query->whereJsonContains('meal_type', $mealType);
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
