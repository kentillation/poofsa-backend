<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CashierAuthController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\KitchenAuthController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\BaristaAuthController;
use App\Http\Controllers\BaristaController;
use App\Http\Controllers\OpenController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ShopModel;
use App\Models\BranchModel;
use App\Models\AdminModel;
use App\Models\CashierModel;
use App\Models\KitchenModel;
use App\Models\BaristaModel;

// ADMIN
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/admin/logout', [AdminAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/admin/save-branch', [AdminController::class, 'saveBranch']);
Route::middleware('auth:sanctum')->post('/admin/save-stock', [AdminController::class, 'saveStock']);
Route::middleware('auth:sanctum')->post('/admin/save-product', [AdminController::class, 'saveProduct']);
Route::middleware('auth:sanctum')->post('/admin/save-product-ingredients', [AdminController::class, 'saveProductIngredients']);
Route::middleware('auth:sanctum')->put('/admin/update-stock/{stock_id}', [AdminController::class, 'updateStock']);
Route::middleware('auth:sanctum')->put('/admin/update-product/{product_id}', [AdminController::class, 'updateProduct']);
Route::middleware('auth:sanctum')->put('/admin/update-product-ingredients/{ingredient_id}', [AdminController::class, 'updateProductIngredients']);
Route::middleware('auth:sanctum')->put('/admin/update-void/{branch_id}', [AdminController::class, 'updateVoidOrder']);
Route::middleware('auth:sanctum')->get('/admin/shop-branches', [AdminController::class, 'getShopBranches']);
Route::middleware('auth:sanctum')->get('/admin/branch-details/{branchName}', [AdminController::class, 'getBranchDetails']);
Route::middleware('auth:sanctum')->get('/admin/products/{branch_id}', [AdminController::class, 'getProducts']);
Route::middleware('auth:sanctum')->get('/admin/product-alone/{product_id}', [AdminController::class, 'getProductAlone']);
Route::middleware('auth:sanctum')->get('/admin/stocks/{branch_id}', [AdminController::class, 'getStocks']);
Route::middleware('auth:sanctum')->get('/admin/stocks-report/{branch_id}', [AdminController::class, 'getStocksReport']);
Route::middleware('auth:sanctum')->get('/admin/stocks-name/{branch_id}/{stock_id}', [AdminController::class, 'getStocksNameBasedId']);
Route::middleware('auth:sanctum')->get('/admin/stocks-name-only/{branch_id}', [AdminController::class, 'getStocksList']);
Route::middleware('auth:sanctum')->get('/admin/stocks-history/{branch_id}', [AdminController::class, 'getStocksHistory']);
Route::middleware('auth:sanctum')->get('/admin/low-stocks', [AdminController::class, 'getStockNotifQty']);
Route::middleware('auth:sanctum')->get('/admin/products-history/{branch_id}', [AdminController::class, 'getProductsHistory']);
Route::middleware('auth:sanctum')->get('/admin/ingredients/{product_id}', [AdminController::class, 'getIngredientsByProduct']);
Route::middleware('auth:sanctum')->get('/admin/all-orders/{branchId}', [AdminController::class, 'getOrdersByDateType']);
Route::middleware('auth:sanctum')->get('/admin/gross-sales-by-date/{branchId}', [AdminController::class, 'getSalesByDateType']);
Route::middleware('auth:sanctum')->get('/admin/gross-sales-only/{branchId}', [AdminController::class, 'getGrossSalesOnly']);
Route::middleware('auth:sanctum')->get('/admin/sales-by-month/{branchId}', [AdminController::class, 'getSalesByMonth']);
Route::middleware('auth:sanctum')->get('/admin/orders-only/{branchId}', [AdminController::class, 'getOrdersOnly']);
Route::middleware('auth:sanctum')->get('/admin/products-only/{branchId}', [AdminController::class, 'getProductsOnly']);
Route::middleware('auth:sanctum')->get('/admin/stocks-only/{branchId}', [AdminController::class, 'getStocksOnly']);
Route::middleware('auth:sanctum')->get('/admin/void-orders/{branchId}', [AdminController::class, 'getVoid']);
Route::middleware('auth:sanctum')->get('/admin/void-status', [AdminController::class, 'getVoidStatus']);
Route::middleware('auth:sanctum')->get('/admin/product-temperature-option', [AdminController::class, 'getProductTemperatures']);
Route::middleware('auth:sanctum')->get('/admin/product-size-option', [AdminController::class, 'getProductSizes']);
Route::middleware('auth:sanctum')->get('/admin/product-category-option', [AdminController::class, 'getProductCategories']);
Route::middleware('auth:sanctum')->get('/admin/product-availability-option', [AdminController::class, 'getProductAvailabilities']);
Route::middleware('auth:sanctum')->get('/admin/product-station-option', [AdminController::class, 'getProductStation']);
Route::middleware('auth:sanctum')->get('/admin/stock-unit-option', [AdminController::class, 'getStockUnits']);

// CASHIER
Route::post('/cashier/login', [CashierAuthController::class, 'login']);
Route::middleware('auth:cashier-api')->post('/cashier/logout', [CashierAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/cashier/current-orders', [CashierController::class, 'getCurrentOrders']);
Route::middleware('auth:sanctum')->put('/cashier/update-order-status', [CashierController::class, 'updateOrderStatus']);
Route::middleware('auth:sanctum')->post('/cashier/submit-transaction', [CashierController::class, 'submitTransaction']);
Route::middleware('auth:sanctum')->post('/cashier/save-void', [CashierController::class, 'saveVoid']);

// KITCHEN
Route::post('/kitchen/login', [KitchenAuthController::class, 'login']);
Route::middleware('auth:kitchen-api')->post('/kitchen/logout', [KitchenAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/kitchen/current-orders', [KitchenController::class, 'getCurrentOrders']);
Route::middleware('auth:sanctum')->get('/kitchen/kitchen-product-details/{transactionId}', [KitchenController::class, 'getKitchenProductDetails']);
Route::middleware('auth:sanctum')->put('/kitchen/update-kitchen-product-status', [KitchenController::class, 'updateKitchenProductStatus']);
Route::middleware('auth:sanctum')->get('/kitchen/station-status', [KitchenController::class, 'getStationStatus']);

// BARISTA
Route::post('/barista/login', [BaristaAuthController::class, 'login']);
Route::middleware('auth:barista-api')->post('/barista/logout', [BaristaAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/barista/current-orders', [BaristaController::class, 'getCurrentOrders']);
Route::middleware('auth:sanctum')->get('/barista/barista-product-details/{transactionId}', [BaristaController::class, 'getBaristaProductDetails']);
Route::middleware('auth:sanctum')->get('/barista/station-status', [BaristaController::class, 'getStationStatus']);
Route::middleware('auth:sanctum')->put('/barista/update-barista-product-status', [BaristaController::class, 'updateBaristaProductStatus']);

// OPEN
Route::middleware('auth:sanctum')->get('/open/shop-branches', [OpenController::class, 'getShopBranches']);
Route::middleware('auth:sanctum')->get('/open/branch-details/{branchName}', [OpenController::class, 'getBranchDetails']);
Route::middleware('auth:sanctum')->get('/open/shop-name', [OpenController::class, 'getShopName']);
Route::middleware('auth:sanctum')->get('/open/products', [OpenController::class, 'getProducts']);
Route::middleware('auth:sanctum')->get('/open/stocks/{branch_id}', [OpenController::class, 'getStocks']);
Route::middleware('auth:sanctum')->get('/open/low-stocks/{branch_id}', [OpenController::class, 'getStockNotifQty']);
Route::middleware('auth:sanctum')->get('/open/product-temperature-option', [OpenController::class, 'getProductTemperatures']);
Route::middleware('auth:sanctum')->get('/open/product-size-option', [OpenController::class, 'getProductSizes']);
Route::middleware('auth:sanctum')->get('/open/product-category-option', [OpenController::class, 'getProductCategories']);
Route::middleware('auth:sanctum')->get('/open/product-availability-option', [OpenController::class, 'getProductAvailabilities']);
Route::middleware('auth:sanctum')->get('/open/order-status', [OpenController::class, 'getOrderStatus']);
Route::middleware('auth:sanctum')->get('/open/order-details/{referenceNumber}', [OpenController::class, 'getOrderDetails']);
Route::middleware('auth:sanctum')->get('/open/void-orders', [OpenController::class, 'getVoid']);
Route::middleware('auth:sanctum')->get('/open/get-qr/{referenceNumber}', [OpenController::class, 'getQR']);
Route::get('/open/order-details-temp/{referenceNumber}', [OpenController::class, 'getOrderDetailsTemp']);
Route::get('/open/get-qr-temp/{referenceNumber}', [OpenController::class, 'getQRTemp']);

// Route::post('/test-message', function (Request $request) {
//     event(new \App\Events\RealTimeMessage($request->order_status_id));
//     return ['status' => 'Message sent!'];
// });

Route::post('/registerAccount', function (Request $request) {
    $validated = $request->validate([
        'shop_id' => 'required|integer|min:1',
        'shop_name' => 'required|string|max:50',
        'shop_owner' => 'required|string|max:50',
        'shop_location' => 'required|string',
        'shop_email' => 'required|string|email|max:50|unique:tbl_shop,shop_email',
        'shop_contact_number' => 'required|string|max:13',
        'shop_status_id' => 'required|integer|min:1',
        'branch_id' => 'required|integer|min:1',
        'branch_name' => 'required|string|max:50',
        'branch_location' => 'required|string',
        'm_name' => 'required|string|max:50',
        'm_email' => 'required|string|email|max:50|unique:tbl_shop_branch,m_email',
        'contact' => 'required|string|max:13',
        'status_id' => 'required|integer|min:1',
        'admin_name' => 'required|string|max:255',
        'admin_email' => 'required|string|email|max:255|unique:tbl_admin,admin_email',
        'admin_password' => 'required|string|min:8',
        'admin_mpin' => 'required|digits:6|numeric',
        'cashier_name' => 'required|string|max:255',
        'cashier_email' => 'required|string|email|max:255|unique:tbl_cashier,cashier_email',
        'cashier_password' => 'required|string|min:8',
        'cashier_mpin' => 'required|digits:6|numeric',
        'kitchen_name' => 'required|string|max:255',
        'kitchen_email' => 'required|string|email|max:255|unique:tbl_kitchen,kitchen_email',
        'kitchen_password' => 'required|string|min:8',
        'kitchen_mpin' => 'required|digits:6|numeric',
        'barista_name' => 'required|string|max:255',
        'barista_email' => 'required|string|email|max:255|unique:tbl_barista,barista_email',
        'barista_password' => 'required|string|min:8',
        'barista_mpin' => 'required|digits:6|numeric',
    ]);
    DB::beginTransaction();
    try {
        $shop = ShopModel::create([
            'shop_id' => $validated['shop_id'],
            'shop_name' => $validated['shop_name'],
            'shop_owner' => $validated['shop_owner'],
            'shop_location' => $validated['shop_location'],
            'shop_email' => $validated['shop_email'],
            'shop_contact_number' => $validated['shop_contact_number'],
            'shop_status_id' => $validated['shop_status_id'],
        ]);
        $branch = BranchModel::create([
            'branch_id' => 8,
            'shop_id' => $validated['shop_id'],
            'branch_name' => $validated['branch_name'],
            'branch_location' => $validated['branch_location'],
            'm_name' => $validated['m_name'],
            'm_email' => $validated['m_email'],
            'contact' => $validated['contact'],
            'status_id' => $validated['status_id'],
        ]);
        $admin = AdminModel::create([
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => Hash::make($validated['admin_password']),
            'admin_mpin' => $validated['admin_mpin'],
            'shop_id' => $validated['shop_id'],
        ]);
        $cashier = CashierModel::create([
            'cashier_name' => $validated['cashier_name'],
            'cashier_email' => $validated['cashier_email'],
            'cashier_password' => Hash::make($validated['cashier_password']),
            'cashier_mpin' => $validated['cashier_mpin'],
            'shop_id' => $validated['shop_id'],
            'branch_id' => 5,
        ]);
        $kitchen = KitchenModel::create([
            'kitchen_name' => $validated['kitchen_name'],
            'kitchen_email' => $validated['kitchen_email'],
            'kitchen_password' => Hash::make($validated['kitchen_password']),
            'kitchen_mpin' => $validated['kitchen_mpin'],
            'shop_id' => $validated['shop_id'],
            'branch_id' => 5,
        ]);
        $barista = BaristaModel::create([
            'barista_name' => $validated['barista_name'],
            'barista_email' => $validated['barista_email'],
            'barista_password' => Hash::make($validated['barista_password']),
            'barista_mpin' => $validated['barista_mpin'],
            'shop_id' => $validated['shop_id'],
            'branch_id' => 5,
        ]);
        $token = $admin->createToken('auth-token')->plainTextToken;
        DB::commit();
        return response()->json([
            'message' => 'Registration successful',
            'shop' => $shop,
            'branch' => $branch,
            'admin' => $admin->makeHidden(['admin_password', 'admin_mpin']),
            'cashier' => $cashier->makeHidden(['cashier_password', 'cashier_mpin']),
            'kitchen' => $kitchen->makeHidden(['kitchen_password', 'kitchen_mpin']),
            'barista' => $barista->makeHidden(['barista_password', 'barista_mpin']),
            'token' => $token,
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Registration failed',
            'error' => $e->getMessage()
        ], 500);
    }
});
