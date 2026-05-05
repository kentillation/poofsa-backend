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
use App\Models\ProductBaseCategoryModel;

class PublicController extends Controller
{
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
                'shop_email' => $validated['shop_email'],
                'shop_contact_number' => $validated['shop_contact_number'],
            ]);
            $shopId = $shop->shop_id;

            BranchModel::create([
                'shop_id' => $shopId,
                'branch_name' => 'Main',
                'branch_manager_name' => $validated['shop_owner'],
                'branch_contact_number' => $validated['shop_contact_number'],
                'branch_address' => $validated['shop_address'],
                'branch_latitude' => $validated['branch_latitude'],
                'branch_longitude' => $validated['branch_longitude'],
                'open_at' => $validated['open_at'],
                'close_at' => $validated['close_at'],
                'is_overnight' => $isOvernight,
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

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email.',
                    'error' => $e->getMessage() // THIS will reveal the real issue
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
}
