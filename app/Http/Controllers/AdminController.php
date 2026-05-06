<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\GetRequest;
use App\Http\Resources\ShopResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCountResource;
use App\Http\Resources\OrderReportResource;
use App\Http\Resources\SaleCountResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductHistoryResource;
use App\Http\Resources\StockResource;
use App\Http\Resources\StockHistoryResource;
use App\Actions\Orders\GetOrdersAction;
use App\Actions\Orders\GetOrdersCountAction;
use App\Actions\Orders\GetOrdersReportAction;
use App\Actions\Sales\GetSalesCountAction;
use App\Actions\Products\GetProductsAction;
use App\Actions\Products\GetProductsHistoryAction;
use App\Actions\Stocks\GetStocksAction;
use App\Actions\Stocks\GetStocksHistoryAction;
use App\Models\ShopModel;
use App\Models\BranchModel;
use App\Models\ProductItemsModel;
use App\Models\ProductsModel;
use App\Models\StocksHistoryModel;
use App\Models\TemperatureModel;
use App\Models\SizeModel;
use App\Models\CategoryModel;
use App\Models\AvailabilityModel;
use App\Models\UnitModel;
use App\Models\StationModel;
use App\Models\OrderItemsModel;
use App\Models\VoidOrdersModel;
use App\Models\VoidStatusModel;
use App\Models\SalesModel;
use App\Models\IngredientsModel;
use App\Models\StockBatchesModel;
use App\Services\ShopService;
use App\Services\ProductService;
use App\Services\StockService;

class AdminController extends Controller
{

    protected function getShopId(): int
    {
        $user = auth('sanctum')->user();
        return $user->shop_id;
    }

    protected function getUserId(): int
    {
        $user = auth('sanctum')->user();
        return $user->admin_id;
    }

    /**** Admin ****/
    public function verifyAdmin(Request $request)
    {
        return response()->json([
            'valid' => true,
            'admin' => $request->user(),
            'shop_id' => $request->user()->shop_id,
            'shop_name' => $request->user()->shop->shop_name ?? null
        ]);
    }

    /**** Shop ****/
    public function getShopDetails($shopId)
    {
        try {
            $shop = ShopModel::find($shopId);

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop not found'
                ], 404);
            }

            // Return as object {} not array []
            return response()->json([
                'success' => true,
                'data' => [
                    'shop_id' => $shop->shop_id,
                    'shop_owner' => $shop->shop_owner,
                    'shop_name' => $shop->shop_name,
                    'shop_type' => $shop->shop_type,
                    'shop_email' => $shop->shop_email,
                    'shop_contact_number' => $shop->shop_contact_number,

                    // 'shop_address' => $shop->shop_address,
                    // 'is_active' => $shop->is_active,
                    // 'open_at' => $shop->open_at ? date('H:i', strtotime($shop->open_at)) : null,
                    // 'close_at' => $shop->close_at ? date('H:i', strtotime($shop->close_at)) : null,
                    // 'is_overnight' => $shop->is_overnight,

                    'created_at' => $shop->created_at ? $shop->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $shop->updated_at ? $shop->updated_at->format('Y-m-d H:i:s') : null,

                    // Image fields
                    'thumbnail_url' => $shop->thumbnail_url,
                    'standard_image_url' => $shop->standard_image_url,
                    'image_size_kb' => $shop->image_size_kb,
                    'has_image' => $shop->has_image,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Fetch shop details failed', [
                'error' => $e->getMessage(),
                'shop_id' => $shopId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shop details'
            ], 500);
        }
    }

    public function updateShop(Request $request, $shopId)
    {
        try {
            // Validate request
            $rules = [
                'admin_id' => 'required|integer|min:1',
                'shop_owner' => 'required|string|max:50',
                'shop_name' => 'required|string|max:30',
                'shop_type' => 'required|string|max:50',
                'shop_email' => 'required|email|max:191',
                'shop_contact_number' => 'required|string|max:13',
            ];

            // Add image validation if present
            if ($request->hasFile('image')) {
                $rules['image'] = 'image|mimes:jpeg,png,jpg,webp|max:2048';
                Log::info('Image detected in request', [
                    'shop_id' => $shopId,
                    'file_name' => $request->file('image')->getClientOriginalName(),
                    'file_size' => $request->file('image')->getSize()
                ]);
            }

            $validated = $request->validate($rules);
            $adminId = $validated['admin_id'];

            $result = ShopService::updateShopService($request, $shopId, $adminId);

            if ($result['success']) {
                // Return as object {} not array []
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'shop_id' => $result['data']->shop_id,
                        'shop_owner' => $result['data']->shop_owner,
                        'shop_name' => $result['data']->shop_name,
                        'shop_type' => $result['data']->shop_type,
                        'shop_email' => $result['data']->shop_email,
                        'shop_contact_number' => $result['data']->shop_contact_number,
                        'created_at' => $result['data']->created_at ? $result['data']->created_at->format('Y-m-d H:i:s') : null,
                        'updated_at' => $result['data']->updated_at ? $result['data']->updated_at->format('Y-m-d H:i:s') : null,
                        'thumbnail_url' => $result['data']->thumbnail_url,
                        'standard_image_url' => $result['data']->standard_image_url,
                        'image_size_kb' => $result['data']->image_size_kb,
                        'has_image' => $result['data']->has_image,
                    ],
                    'changes' => $result['changes'] ?? []
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Shop update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'shop_id' => $shopId,
                'admin_id' => $adminId ?? null,
                'has_file' => $request->hasFile('image'),
                'file_info' => $request->hasFile('image') ? [
                    'name' => $request->file('image')->getClientOriginalName(),
                    'size' => $request->file('image')->getSize()
                ] : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update shop: ' . $e->getMessage()
            ], 500);
        }
    }

    // Previous code
    // public function updateShop(Request $request, $shopId)
    // {
    //     try {
    //         // Validate request
    //         $rules = [
    //             'admin_id' => 'required|integer|min:1',
    //             'shop_owner' => 'required|string|max:50',
    //             'shop_name' => 'required|string|max:30',
    //             'shop_type' => 'required|string|max:50',
    //             'shop_email' => 'required|email|max:191',
    //             'shop_contact_number' => 'required|string|max:13',
    //             // 'shop_address' => 'required|string',
    //             // 'is_active' => 'boolean',
    //             // 'open_at' => 'required|date_format:H:i',
    //             // 'close_at' => 'required|date_format:H:i',
    //             // 'is_overnight' => 'boolean',
    //         ];

    //         // Add image validation if present
    //         if ($request->hasFile('image')) {
    //             $rules['image'] = 'image|mimes:jpeg,png,jpg,webp|max:2048';
    //             Log::info('Image detected in request', [
    //                 'shop_id' => $shopId,
    //                 'file_name' => $request->file('image')->getClientOriginalName(),
    //                 'file_size' => $request->file('image')->getSize()
    //             ]);
    //         }

    //         $validated = $request->validate($rules);
    //         $adminId = $validated['admin_id'];

    //         $result = ShopService::updateShopService($request, $shopId, $adminId);

    //         if ($result['success']) {
    //             // Return as object {} not array []
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => $result['message'],
    //                 'data' => [
    //                     'shop_id' => $result['data']->shop_id,
    //                     'shop_owner' => $result['data']->shop_owner,
    //                     'shop_name' => $result['data']->shop_name,
    //                     'shop_type' => $result['data']->shop_type,
    //                     'shop_email' => $result['data']->shop_email,
    //                     'shop_contact_number' => $result['data']->shop_contact_number,
    //                     // 'shop_address' => $result['data']->shop_address,
    //                     // 'is_active' => $result['data']->is_active,
    //                     // 'open_at' => $result['data']->open_at ? date('H:i', strtotime($result['data']->open_at)) : null,
    //                     // 'close_at' => $result['data']->close_at ? date('H:i', strtotime($result['data']->close_at)) : null,
    //                     // 'is_overnight' => $result['data']->is_overnight,
    //                     'created_at' => $result['data']->created_at ? $result['data']->created_at->format('Y-m-d H:i:s') : null,
    //                     'updated_at' => $result['data']->updated_at ? $result['data']->updated_at->format('Y-m-d H:i:s') : null,

    //                     // Image fields
    //                     'thumbnail_url' => $result['data']->thumbnail_url,
    //                     'standard_image_url' => $result['data']->standard_image_url,
    //                     'image_size_kb' => $result['data']->image_size_kb,
    //                     'has_image' => $result['data']->has_image,
    //                 ],
    //                 'changes' => $result['changes'] ?? []
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => $result['message']
    //             ], 500);
    //         }
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors()
    //         ], 422);
    //     } catch (\Throwable $e) {
    //         Log::error('Shop update failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'shop_id' => $shopId,
    //             'admin_id' => $adminId ?? null,
    //             'has_file' => $request->hasFile('image'),
    //             'file_info' => $request->hasFile('image') ? [
    //                 'name' => $request->file('image')->getClientOriginalName(),
    //                 'size' => $request->file('image')->getSize()
    //             ] : null
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update shop: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**** Branch ****/
    public function saveBranch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_name' => 'required|string',
            'branch_address' => 'required|string',
            'branch_manager_name' => 'required|string',
            'contact' => 'required|string',
            'branch_email' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        try {
            $shopId = $this->getShopId();
            DB::transaction(function () use ($request, $shopId) {
                $newBranch = BranchModel::create([
                    'shop_id' => $shopId,
                    'branch_name' => $request->input('branch_name'),
                    'branch_address' => $request->input('branch_address'),
                    'branch_manager_name' => $request->input('branch_manager_name'),
                    'branch_email' => $request->input('branch_email'),
                    'contact' => $request->input('contact'),
                    'staff_name' => $request->input('staff_name'),
                    'status_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // If no branch_id is provided, copy from the FIRST branch in the shop
                // $newBranchId = BranchModel::where('shop_id', $shopId)->first()->branch_id;
                $newBranchId = $request->user()->branch_id + 1;

                $userId = $request->user()->admin_id;

                $stocks = IngredientsModel::where('branch_id', $newBranchId)->get();
                $stockMap = [];
                foreach ($stocks as $stock) {
                    $newStock = $stock->replicate();
                    $newStock->branch_id = $newBranch->branch_id;
                    $newStock->user_id = $userId;
                    $newStock->created_at = now();
                    $newStock->updated_at = now();
                    $newStock->save();
                    $stockMap[$stock->ingredient_id] = $newStock->ingredient_id;
                }

                $products = ProductsModel::where('branch_id', $newBranchId)->get();
                $productMap = [];
                foreach ($products as $product) {
                    $newProduct = $product->replicate();
                    $newProduct->branch_id = $newBranch->branch_id;
                    $newProduct->user_id = $userId;
                    $newProduct->created_at = now();
                    $newProduct->updated_at = now();
                    $newProduct->save();
                    $productMap[$product->product_id] = $newProduct->product_id;
                }

                $ingredients = ProductItemsModel::where('branch_id', $newBranchId)->get();
                foreach ($ingredients as $ingredient) {
                    $newIngredient = $ingredient->replicate();
                    $newIngredient->branch_id = $newBranch->branch_id;
                    $newIngredient->product_id = $productMap[$ingredient->product_id] ?? null;
                    $newIngredient->ingredient_id = $stockMap[$ingredient->ingredient_id] ?? null;
                    $newIngredient->created_at = now();
                    $newIngredient->updated_at = now();
                    $newIngredient->save();
                }
            });
            return response()->json(['message' => 'New branch created successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getShopBranches()
    {
        try {
            $shopId = $this->getShopId();
            $branches = BranchModel::where('shop_id', $shopId)
                ->where('is_active', 1)
                ->pluck('branch_name');
            return response()->json([
                'success' => true,
                'branches' => $branches
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getBranchDetails($branchName)
    {
        try {
            $shopId = $this->getShopId();
            $branchData = BranchModel::select(
                'tbl_shops.shop_name',
                'tbl_shop_branch.shop_id',
                'tbl_shop_branch.branch_id',
                'tbl_shop_branch.branch_name',
                'tbl_shop_branch.branch_address',
                'tbl_shop_branch.branch_manager_name',
                'tbl_shop_branch.branch_contact_number',
                'tbl_shop_branch.updated_at',
                'tbl_admin.admin_name',
                // 'tbl_cashier.cashier_name',
                // 'tbl_cashier.cashier_email',
                // 'tbl_barista.barista_name',
                // 'tbl_barista.barista_email',
                // 'tbl_kitchen_personnel.kitchen_personnel_name',
                // 'tbl_kitchen_personnel.kitchen_personnel_email',
            )
                ->join('tbl_shops', 'tbl_shop_branch.shop_id', '=', 'tbl_shops.shop_id')
                ->join('tbl_admin', 'tbl_shops.shop_id', '=', 'tbl_admin.shop_id')
                // ->join('tbl_cashier', 'tbl_shops.shop_id', '=', 'tbl_cashier.shop_id')
                // ->join('tbl_barista', 'tbl_shops.shop_id', '=', 'tbl_barista.shop_id')
                // ->join('tbl_kitchen_personnel', 'tbl_shops.shop_id', '=', 'tbl_kitchen_personnel.shop_id')
                ->where('tbl_shop_branch.branch_name', urldecode($branchName))
                ->where('tbl_shop_branch.shop_id', $shopId)
                ->first();
            // if ($branchData && $branchData->branch_address) {
            //     $branchData->branch_address = str_replace(',', '', $branchData->branch_address);
            // }
            if (!$branchData) {
                return response()->json(['error' => 'Branch not found'], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Branch details fetched successfully',
                'data' => $branchData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching branch details!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateBranchDetails(Request $request)
    {
        $shopId = $this->getShopId();
        $userId = $this->getUserId();

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|min:1',
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'required|string|max:500',
            'branch_latitude' => 'nullable|numeric',
            'branch_longitude' => 'nullable|numeric',
            'branch_manager_name' => 'required|string|max:255',
            'branch_contact_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $branchId = $validator->validated()['branch_id'];

        try {

            // Find the branch
            $branch = BranchModel::where('branch_id', $branchId)
                ->where('shop_id', $shopId)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or you do not have permission to update this branch'
                ], 404);
            }

            // Track changes for logging
            $oldData = $branch->only([
                'branch_name',
                'branch_address',
                'branch_latitude',
                'branch_longitude',
                'branch_manager_name',
                'branch_contact_number'
            ]);

            // Update branch
            $branch->branch_name = $request->input('branch_name');
            $branch->branch_address = $request->input('branch_address');
            $branch->branch_latitude = $request->input('branch_latitude');
            $branch->branch_longitude = $request->input('branch_longitude');
            $branch->branch_manager_name = $request->input('branch_manager_name');
            $branch->branch_contact_number = $request->input('branch_contact_number');
            $branch->updated_at = now();
            $branch->save();

            $newData = $branch->only([
                'branch_name',
                'branch_address',
                'branch_latitude',
                'branch_longitude',
                'branch_manager_name',
                'branch_contact_number'
            ]);

            // Log the changes (optional - if you have a branch history table)
            $changes = [];
            foreach ($oldData as $key => $value) {
                if ($oldData[$key] != $newData[$key]) {
                    $changes[$key] = [
                        'old' => $oldData[$key],
                        'new' => $newData[$key]
                    ];
                }
            }

            Log::info('Branch updated', [
                'branch_id' => $branchId,
                'branch_latitude' => $branch->branch_latitude,
                'branch_longitude' => $branch->branch_longitude,
                'shop_id' => $shopId,
                'user_id' => $userId,
                'changes' => $changes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Branch info updated successfully',
                // 'data' => $branch,
                'changes' => $changes
            ], 200);
        } catch (\Exception $e) {
            Log::error('Branch update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'branch_id' => $branchId,
                'user_id' => $this->getUserId() ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**** Orders ****/
    // NEW
    public function getOrders(GetRequest $request, GetOrdersAction $action)
    {
        $result = $action->execute(
            shopId: $this->getShopId(),
            branchId: $request->branch_id,
            search: $request->search,
            perPage: $request->itemsPerPage ?? 10
        );

        return OrderResource::collection($result);
    }

    // NEW
    public function getTotalOrdersCount(GetRequest $request, GetOrdersCountAction $action)
    {
        $result = $action->execute(
            shopId: $this->getShopId(),
            branchId: $request->branch_id,
        );

        return new OrderCountResource($result);
    }

    // NEW
    public function getOrdersReport(GetRequest $request, GetOrdersReportAction $action)
    {
        $result = $action->execute(
            shopId: $this->getShopId(),
            branchId: $request->branch_id,
            perPage: $request->itemsPerPage ?? 10,
            dateType: $request->query('date_filter'),
        );

        return OrderReportResource::collection($result);
    }

    /**** Sales ****/

    // UPDATED
    public function getSalesByDateType($branchId, Request $request)
    {
        try {
            $shopId = $this->getShopId();
            $dateType = $request->query('date_filter');
            $query = OrderItemsModel::select(
                'tbl_order_items.product_id',
                DB::raw('SUM(tbl_order_items.quantity) as total_quantity'),
                DB::raw('SUM(tbl_order_items.quantity * tbl_products.base_price) as gross_sales'),
                DB::raw('MAX(tbl_order_items.updated_at) as updated_at'),
                'tbl_orders.order_id',
                'tbl_orders.shop_id',
                'tbl_orders.branch_id',
                'tbl_sales.sales_status_id',
                'tbl_sales_status.sales_status',
                'tbl_products.product_name',
                'tbl_products.base_price',
                'tbl_products.category_id',
                'tbl_product_category.category_label',
                'tbl_product_temp.temp_label',
                'tbl_product_size.size_label'
            )
                ->join('tbl_orders', 'tbl_order_items.order_id', '=', 'tbl_orders.order_id')
                ->join('tbl_sales', 'tbl_order_items.order_id', '=', 'tbl_sales.order_id')
                ->join('tbl_sales_status', 'tbl_sales.sales_status_id', '=', 'tbl_sales_status.sales_status_id')
                ->join('tbl_products', 'tbl_order_items.product_id', '=', 'tbl_products.product_id')
                ->join('tbl_product_temp', 'tbl_products.temp_id', '=', 'tbl_product_temp.product_temp_id')
                ->join('tbl_product_size', 'tbl_products.size_id', '=', 'tbl_product_size.product_size_id')
                ->join('tbl_product_category', 'tbl_products.category_id', '=', 'tbl_product_category.product_category_id')
                ->where('tbl_orders.shop_id', $shopId)
                ->where('tbl_orders.branch_id', $branchId)
                ->where('tbl_sales.sales_status_id', 4) // PAID
                ->groupBy(
                    'tbl_order_items.product_id',
                    'tbl_orders.order_id',
                    'tbl_orders.shop_id',
                    'tbl_orders.branch_id',
                    'tbl_sales.sales_status_id',
                    'tbl_sales_status.sales_status',
                    'tbl_products.product_name',
                    'tbl_products.base_price',
                    'tbl_products.category_id',
                    'tbl_product_category.category_label',
                    'tbl_product_temp.temp_label',
                    'tbl_product_size.size_label'
                );

            if ($dateType) {
                switch ($dateType) {
                    case 1:
                        $query->whereDate('tbl_order_items.updated_at', now());
                        break;
                    case 2:
                        $query->whereDate('tbl_order_items.updated_at', now()->subDay());
                        break;
                    case 3:
                        $query->whereDate('tbl_order_items.updated_at', '>=', now()->subDays(7));
                        break;
                    case 4:
                        $query->whereDate('tbl_order_items.updated_at', '>=', now()->startOfWeek());
                        break;
                    case 5:
                        $query->whereDate('tbl_order_items.updated_at', '>=', now()->subDays(30));
                        break;
                    case 6:
                        $query->whereMonth('tbl_order_items.updated_at', now()->month);
                        break;
                    case 7:
                        $query->whereMonth('tbl_order_items.updated_at', now()->subMonth()->month);
                        break;
                }
            }
            $totalSalesQuery = OrderItemsModel::select(
                DB::raw(
                    'SUM(tbl_order_items.quantity * tbl_products.base_price * (1 - tbl_sales.discount_amount/100)) as discounted_sales'
                )
            )
                ->join('tbl_orders', 'tbl_order_items.order_id', '=', 'tbl_orders.order_id')
                ->join('tbl_sales', 'tbl_order_items.order_id', '=', 'tbl_sales.order_id')
                ->join('tbl_products', 'tbl_order_items.product_id', '=', 'tbl_products.product_id')
                ->where('tbl_orders.shop_id', $shopId)
                ->where('tbl_orders.branch_id', $branchId)
                ->where('tbl_sales.sales_status_id', 4);

            if ($dateType) {
                switch ($dateType) {
                    case 1:
                        $totalSalesQuery->whereDate('tbl_order_items.updated_at', now());
                        break;
                    case 2:
                        $totalSalesQuery->whereDate('tbl_order_items.updated_at', now()->subDay());
                        break;
                    case 3:
                        $totalSalesQuery->whereDate('tbl_order_items.updated_at', '>=', now()->subDays(7));
                        break;
                    case 4:
                        $totalSalesQuery->whereDate('tbl_order_items.updated_at', '>=', now()->startOfWeek());
                        break;
                    case 5:
                        $totalSalesQuery->whereDate('tbl_order_items.updated_at', '>=', now()->subDays(30));
                        break;
                    case 6:
                        $totalSalesQuery->whereMonth('tbl_order_items.updated_at', now()->month);
                        break;
                    case 7:
                        $totalSalesQuery->whereMonth('tbl_order_items.updated_at', now()->subMonth()->month);
                        break;
                }
            }
            $totalSales = $totalSalesQuery->first()->discounted_sales ?? 0;
            $data = $query->orderBy('gross_sales', 'desc')->get();
            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No sales found!' : 'Sales fetched successfully!',
                'data' => $data,
                'total_sales' => $totalSales,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching sales!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getSalesByMonth($branchId, Request $request)
    {
        // For Analytics
        try {
            $shopId = $this->getShopId();
            $year = $request->query('year', date('Y'));
            $dateType = $request->query('date_filter');
            $day = $request->query('day', date('d'));
            $query = SalesModel::select(
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('MAX(updated_at) as updated_at'),
            )
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->where('sales_status_id', 1);
            if ($dateType) {
                $query->whereYear('updated_at', $year)
                    ->whereMonth('updated_at', $dateType);
            }
            $data = $query->groupBy(DB::raw('DATE(updated_at)'))
                ->orderBy('total_sales', 'desc')
                ->get();
            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No sales found!' : 'Sales by month fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching sales!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getGrossSalesOnly($branchId, Request $request)
    {
        try {
            $shopId = $this->getShopId();
            $dateType = $request->query('date_filter');
            $query = SalesModel::select(
                DB::raw('SUM(tbl_sales.total_amount) as total_sales'),
                DB::raw('MAX(tbl_sales.updated_at) as updated_at'),
            )
                ->where('tbl_sales.shop_id', $shopId)
                ->where('tbl_sales.branch_id', $branchId)
                ->where('tbl_sales.sales_status_id', 1);
            if ($dateType) {
                $query->whereMonth('tbl_sales.updated_at', $dateType)
                    ->whereYear('tbl_sales.updated_at', date('Y'));
            }
            $grossSales = $query->first();
            return response()->json([
                'status' => true,
                'message' => 'Total sales fetched successfully!',
                'data' => [
                    'total_sales' => $grossSales->total_sales ?? 0
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching gross sales!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // NEW
    public function getTotalSalesCount(GetRequest $request, GetSalesCountAction $action)
    {
        $result = $action->execute(
            shopId: $this->getShopId(),
            branchId: $request->branch_id,
        );

        return new SaleCountResource($result);
    }

    /**** Products ****/

    // DONE
    public function saveProducts(Request $request)
    {
        try {
            $shopId = $this->getShopId();
            $userId = $this->getUserId();

            $result = ProductService::saveProductsService($request, $shopId, $userId);

            if ($result['success']) {
                return response()->json([
                    'status' => true,
                    'message' => $result['message'],
                    'saved_count' => $result['saved_count'],
                    'skipped_count' => $result['skipped_count'],
                    'skipped' => $result['skipped']
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => $result['message']
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error saving products!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DONE
    public function updateProduct(Request $request, $productId)
    {
        $userId = $this->getUserId();
        $shopId = $this->getShopId();

        try {

            $result = ProductService::updateProductService($request, $productId, $shopId, $userId);
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'],
                    'changes' => $result['changes'] ?? []
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
        } catch (\Throwable $e) {
            Log::error('Product update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_name' => $result->product_name ?? null,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product. Please try again.'
            ], 500);
        }
    }

    // NEW
    public function getProducts(GetRequest $request, GetProductsAction $action)
    {
        $result = $action->execute(
            shopId: $request->shop_id,
            branchId: $request->branch_id,
            perPage: $request->itemsPerPage,
            search: $request->search,
        );

        // return ProductResource::collection($result);
        return response()->json([
            'status' => true,
            'data' => ProductResource::collection($result),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ]
        ]);
    }

    // NEW
    public function getProductsHistory(GetRequest $request, GetProductsHistoryAction $action)
    {
        $result = $action->execute(
            shopId: $request->shop_id,
            branchId: $request->branch_id,
            perPage: $request->itemsPerPage,
            search: $request->search
        );

        return ProductHistoryResource::collection($result);
    }

    // DONE
    public function getTotalProductsCount($branchId)
    {
        // For Dashboard
        try {
            $shopId = $this->getShopId();
            $totalProducts = ProductService::getTotalProductsCountService($shopId, $branchId);

            return response()->json([
                'status' => true,
                'message' => 'Total products fetched successfully!',
                'data' => [
                    'total_products' => $totalProducts->total_products ?? 0
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching total orders!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**** Product Ingredients ****/

    // DONE
    public function saveProductIngredients(Request $request)
    {
        $shopId = $this->getShopId();
        $request->validate([
            '*.shop_id' => 'required|integer',
            '*.branch_id' => 'required|integer',
            '*.product_id' => 'required|integer',
            '*.ingredient_id' => 'required|integer',
            '*.quantity_required' => 'required|numeric',
            '*.ingredient_capital' => 'required|numeric',
        ]);
        try {
            foreach ($request->all() as $item) {
                ProductItemsModel::create([
                    'shop_id' => $shopId,
                    'branch_id' => $item['branch_id'],
                    'product_id' => $item['product_id'],
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity_required' => $item['quantity_required'],
                    'ingredient_capital' => $item['ingredient_capital'],
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Product items saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error saving product items!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DONE
    public function updateProductItems(Request $request, $productItemId)
    {
        try {
            $shopId = $this->getShopId();
            $userId = $this->getUserId();

            $result = ProductService::updateProductItemsService($request, $productItemId, $shopId, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Product items updated successfully',
                'data' => $result['productItems'],
                'changes' => $result['changes'],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating product!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DONE
    public function getProductItems($productId)
    {
        try {
            $shopId = $this->getShopId();
            $productItems = ProductService::getProductItemsService($shopId, $productId);

            return response()->json([
                'status' => true,
                'message' => $productItems->isEmpty() ? 'No product items found!' : 'Product items fetched successfully!',
                'data' => $productItems
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching product items!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**** Stocks ****/

    // DONE
    public function saveStock(Request $request)
    {
        $shopId = $this->getShopId();
        $request->validate([
            '*.ingredient_name' => 'required|string',
            '*.alert_quantity' => 'required|numeric',
            '*.base_unit_id' => 'required|integer',
            '*.branch_id' => 'required|integer',
            '*.quantity_received' => 'required|numeric', // for tbl_stock_batches
            '*.unit_cost' => 'required|numeric', // for tbl_stock_batches
            // 'expiry_date' => 'required|string', // for tbl_stock_batches
        ]);
        try {
            foreach ($request->all() as $item) {
                $branchId = $item['branch_id'];
                $ingredient = new IngredientsModel();
                $ingredient->ingredient_name = $item['ingredient_name'];
                $ingredient->base_unit_id = $item['base_unit_id'];
                $ingredient->alert_quantity = $item['alert_quantity'];
                $ingredient->availability_id = 1;
                $ingredient->shop_id = $shopId;
                $ingredient->branch_id = $branchId;
                $ingredient->save();

                $ingredientId = $ingredient->ingredient_id;

                $batchCode = 'BATCH-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

                $quantityRemaining = StockBatchesModel::where('ingredient_id', $ingredientId)->sum('quantity_remaining');

                StockBatchesModel::create([
                    'ingredient_id' => $ingredientId,
                    'batch_code' => $batchCode,
                    'unit_cost' => $item['unit_cost'],
                    'quantity_received' => $item['quantity_received'],
                    'quantity_remaining' => $quantityRemaining + $item['quantity_received'],
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                ]);

                StocksHistoryModel::create([
                    'ingredient_id' => $ingredientId,
                    'modified_type_id' => 1, // SAVE
                    'description' => 'New Stock Saved',
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'user_id' => $this->getUserId(),
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Stocks has been saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error saving stocks!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStock(Request $request, $ingredientId)
    {
        try {
            $userId = $this->getUserId();
            $shopId = $this->getShopId();

            $result = StockService::updateStockService($request, $ingredientId, $shopId, $userId);
            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully',
                'data' => $result['stock'],
                'changes' => $result['changes'],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Stock update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock. Please try again.'
            ], 500);
        }
    }

    // NEW
    public function getStocks(GetRequest $request, GetStocksAction $action)
    {
        $result = $action->execute(
            shopId: $this->getShopId(),
            branchId: $request->branch_id,
            search: $request->search,
            perPage: $request->itemsPerPage ?? 10
        );

        return StockResource::collection($result);
    }

    // NEW
    public function getStocksHistory(GetRequest $request, GetStocksHistoryAction $action)
    {
        $result = $action->execute(
            shopId: $this->getShopId(),
            branchId: $request->branch_id,
            search: $request->search,
            perPage: $request->itemsPerPage ?? 10
        );

        return StockHistoryResource::collection($result);
    }

    // DONE
    public function getIngredientsName($branchId)
    {
        try {
            $shopId = $this->getShopId();
            $data = IngredientsModel::select(
                'tbl_ingredients.ingredient_id',
                'tbl_ingredients.ingredient_name',
            )
                ->where('tbl_ingredients.shop_id', $shopId)
                ->where('tbl_ingredients.branch_id', $branchId)
                ->orderBy('tbl_ingredients.ingredient_name')
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No items found!' : 'Items fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching items!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getStocksReport($branchId, Request $request)
    {
        try {
            $shopId = $this->getShopId();
            $dateType = $request->query('date_filter');

            $stocksQuery = IngredientsModel::select(
                'tbl_ingredients.ingredient_id',
                'tbl_ingredients.ingredient_name',
                'tbl_ingredients.base_unit_id',
                'tbl_ingredient_unit.unit_label',
                'tbl_ingredient_unit.unit_avb',
                'tbl_stock_batches.quantity_received',
                'tbl_stock_batches.quantity_remaining',
                'tbl_ingredients.updated_at'
            )
                ->join('tbl_stock_batches', 'tbl_ingredients.ingredient_id', '=', 'tbl_stock_batches.ingredient_id')
                ->join('tbl_ingredient_unit', 'tbl_ingredients.base_unit_id', '=', 'tbl_ingredient_unit.ingredient_unit_id')
                ->join('tbl_product_items', 'tbl_ingredients.ingredient_id', '=', 'tbl_product_items.ingredient_id')
                ->join('tbl_order_items', 'tbl_product_items.product_id', '=', 'tbl_order_items.product_id')
                ->join('tbl_orders', 'tbl_order_items.order_id', '=', 'tbl_orders.order_id')
                ->where('tbl_ingredients.shop_id', $shopId)
                ->where('tbl_ingredients.branch_id', $branchId)
                ->where('tbl_orders.order_status_id', 3) // Completed orders only
                ->groupBy(
                    'tbl_ingredients.ingredient_id',
                    'tbl_ingredients.ingredient_name',
                    'tbl_ingredients.base_unit_id',
                    'tbl_ingredient_unit.unit_label',
                    'tbl_ingredient_unit.unit_avb',
                    'tbl_stock_batches.quantity_received',
                    'tbl_stock_batches.quantity_remaining',
                    'tbl_ingredients.updated_at',
                );

            if ($dateType) {
                switch ($dateType) {
                    case 1: // Today
                        $stocksQuery->whereDate('tbl_order_items.updated_at', now());
                        break;
                    case 2: // Yesterday
                        $stocksQuery->whereDate('tbl_order_items.updated_at', now()->subDay());
                        break;
                    case 3: // Last 7 days
                        $stocksQuery->whereDate('tbl_order_items.updated_at', '>=', now()->subDays(7));
                        break;
                    case 4: // This week
                        $stocksQuery->whereDate('tbl_order_items.updated_at', '>=', now()->startOfWeek());
                        break;
                    case 5: // Last 30 days
                        $stocksQuery->whereDate('tbl_order_items.updated_at', '>=', now()->subDays(30));
                        break;
                    case 6: // This month
                        $stocksQuery->whereMonth('tbl_order_items.updated_at', now()->month);
                        break;
                    case 7: // Last month
                        $stocksQuery->whereMonth('tbl_order_items.updated_at', now()->subMonth()->month);
                        break;
                }
            }

            $stocks = $stocksQuery->get();

            foreach ($stocks as $stock) {
                $stockOutQuery = DB::table('tbl_order_items')
                    ->join('tbl_product_items', 'tbl_order_items.product_id', '=', 'tbl_product_items.product_id')
                    ->join('tbl_orders', 'tbl_order_items.order_id', '=', 'tbl_orders.order_id')
                    ->where('tbl_product_items.ingredient_id', $stock->ingredient_id)
                    ->where('tbl_orders.order_status_id', 3) // Completed orders only
                    ->select(
                        DB::raw('SUM(tbl_order_items.quantity * tbl_product_items.quantity_required) as total_usage'),
                        DB::raw('SUM(tbl_order_items.quantity) as total_quantity')
                    );

                if ($dateType) {
                    switch ($dateType) {
                        case 1:
                            $stockOutQuery->whereDate('tbl_order_items.updated_at', now());
                            break;
                        case 2:
                            $stockOutQuery->whereDate('tbl_order_items.updated_at', now()->subDay());
                            break;
                        case 3:
                            $stockOutQuery->whereDate('tbl_order_items.updated_at', '>=', now()->subDays(7));
                            break;
                        case 4:
                            $stockOutQuery->whereDate('tbl_order_items.updated_at', '>=', now()->startOfWeek());
                            break;
                        case 5:
                            $stockOutQuery->whereDate('tbl_order_items.updated_at', '>=', now()->subDays(30));
                            break;
                        case 6:
                            $stockOutQuery->whereMonth('tbl_order_items.updated_at', now()->month);
                            break;
                        case 7:
                            $stockOutQuery->whereMonth('tbl_order_items.updated_at', now()->subMonth()->month);
                            break;
                    }
                }

                $stockOutResult = $stockOutQuery->first();
                $stock->stock_out = $stockOutResult->total_usage ?? 0;
                $stock->total_quantity = $stockOutResult->total_quantity ?? 0;
                $stock->updated_at = date('Y-m-d H:i:s', strtotime($stock->updated_at));
            }

            return response()->json([
                'status' => true,
                'message' => $stocks->isEmpty() ? 'No stocks found in orders!' : 'Stocks report fetched successfully!',
                'data' => $stocks
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stocks report!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getLowStock($branchId)
    {
        try {
            $shopId = $this->getShopId();

            $lowStock = StockService::lowStockService($shopId, $branchId);

            return response()->json([
                'status' => true,
                'message' => $lowStock->isEmpty()
                    ? 'No low-stock ingredients!'
                    : 'Low-stock ingredients fetched successfully!',
                'data' => $lowStock
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching low-stock!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getStocksOnly($branchId)
    {
        try {
            $shopId = $this->getShopId();
            $totalStocks = IngredientsModel::select(
                DB::raw('COUNT(tbl_ingredients.ingredient_id) as total_stocks')
            )
                ->where('tbl_ingredients.shop_id', $shopId)
                ->where('tbl_ingredients.branch_id', $branchId)
                ->first();
            return response()->json([
                'status' => true,
                'message' => 'Total stocks fetched successfully!',
                'data' => [
                    'total_stocks' => $totalStocks->total_stocks ?? 0
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching total stocks!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**** Void ****/

    // UPDATED
    public function getVoidOrders($branchId, Request $request)
    {
        try {
            $shopId = $this->getShopId();
            $dateType = $request->query('date_filter');
            $voids = VoidOrdersModel::select(
                'tbl_void_orders.void_order_id',
                'tbl_void_orders.reference_number',
                'tbl_void_orders.from_quantity',
                'tbl_void_orders.to_quantity',
                'tbl_void_orders.updated_at',
                'tbl_orders.table_number',
                'tbl_products.product_name',
                'tbl_product_temp.temp_label',
                'tbl_product_size.size_label',
                'tbl_void_status.void_status',
            )
                ->join('tbl_orders', 'tbl_void_orders.order_id', '=', 'tbl_orders.order_id')
                ->join('tbl_products', 'tbl_void_orders.product_id', '=', 'tbl_products.product_id')
                ->join('tbl_product_temp', 'tbl_products.temp_id', '=', 'tbl_product_temp.product_temp_id')
                ->join('tbl_product_size', 'tbl_products.size_id', '=', 'tbl_product_size.product_size_id')
                ->join('tbl_void_status', 'tbl_void_orders.void_status_id', '=', 'tbl_void_status.void_status_id')
                ->where('tbl_void_orders.shop_id', $shopId)
                ->where('tbl_void_orders.branch_id', $branchId);

            if ($dateType) {
                switch ($dateType) {
                    case 1: // Today
                        $voids->whereDate('tbl_void_orders.updated_at', now());
                        break;
                    case 2: // Yesterday
                        $voids->whereDate('tbl_void_orders.updated_at', now()->subDay());
                        break;
                    case 3: // Last 2 days
                        $voids->whereDate('tbl_void_orders.updated_at', '>=', now()->subDays(2));
                        break;
                    case 4: // Last 3 days
                        $voids->whereDate('tbl_void_orders.updated_at', '>=', now()->subDays(3));
                        break;
                    case 5: // Last 4 days
                        $voids->whereDate('tbl_void_orders.updated_at', '>=', now()->subDays(4));
                        break;
                    case 6: // Last 5 days
                        $voids->whereDate('tbl_void_orders.updated_at', '>=', now()->subDays(5));
                        break;
                    case 7: // Last 6 days
                        $voids->whereDate('tbl_void_orders.updated_at', '>=', now()->subDays(6));
                        break;
                    case 8: // Last 7 days
                        $voids->whereDate('tbl_void_orders.updated_at', '>=', now()->subDays(7));
                        break;
                }
            }
            $voids = $voids->orderBy('tbl_orders.table_number', 'desc')->get();
            return response()->json([
                'status' => true,
                'message' => 'Voids fetched successfully',
                'data' => $voids
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching void!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateVoidOrder($branchId, Request $request)
    {
        try {
            $shopId = $this->getShopId();
            $input = $request->all();
            $validator = Validator::make($input, [
                'orderVoidID' => 'required|integer|exists:tbl_void_orders,order_void_id',
                'referenceNumber' => 'required|string|exists:tbl_void_orders,reference_number',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $ordersVoid = VoidOrdersModel::where('order_void_id', $input['orderVoidID'])
                ->where('reference_number', $input['referenceNumber'])
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->first();
            if (!$ordersVoid) {
                return response()->json([
                    'status' => false,
                    'message' => 'Orders not found'
                ], 404);
            }
            $currentStatus = $ordersVoid->void_status_id;
            $nextStatus = $currentStatus % 2 + 1;
            $ordersVoid->void_status_id = $nextStatus;
            $ordersVoid->save();

            // Ask AI for removing item in orders table if quantity = 1

            return response()->json([
                'status' => true,
                'message' => 'Void status updated successfully',
                'data' => [
                    'order_void_id' => $ordersVoid->order_void_id,
                    'reference_number' => $ordersVoid->reference_number,
                    'void_status_id' => $ordersVoid->void_status_id,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Void status update failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to update void status',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // public function getEwalletImage($folderName, $imageFileName)
    // {
    //     $folderName = $this->$folderName;
    //     $imageFileName = $this->$imageFileName;
    //     $folderPath = storage_path('app/e-Wallet_Evidence/' . $folderName . '/' . $imageFileName);
    //     if (!File::exists($folderPath)) {
    //         abort(404, 'Image not found');
    //     }
    //     return response()->file($folderPath, [
    //         'Content-Type' => File::mimeType($folderPath),
    //         'Content-Disposition' => 'inline'
    //     ]);
    // }

    /**** Options ****/

    // UPDATED
    public function getUnits()
    {
        try {
            $data = UnitModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getProductTemperatures()
    {
        try {
            $data = TemperatureModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getProductSizes()
    {
        try {
            $data = SizeModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getProductCategories()
    {
        try {
            $shopId = $this->getShopId();
            $data = CategoryModel::where('shop_id', $shopId)
                ->get();
            // $data = ProductBaseCategoryModel::orderBy('product_base_category')->get();
            // $transformedData = $data->map(function ($item) {
            //     return [
            //         'product_category_id' => $item->product_base_category_id,
            //         'category_label' => $item->product_base_category,
            //         'meal_type' => $item->meal_type,
            //     ];
            // });

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getAvailabilities()
    {
        try {
            $data = AvailabilityModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getProductStation()
    {
        try {
            $data = StationModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getVoidStatus()
    {
        try {
            $data = VoidStatusModel::all();
            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No void statuses found!' : 'Void statuses fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching void statuses!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
