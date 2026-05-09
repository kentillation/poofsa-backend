<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CashierAuthController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\KitchenAuthController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\BaristaAuthController;
use App\Http\Controllers\BaristaController;
use App\Http\Controllers\OpenController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\PaymentController;
use App\Models\DevModel;

// Public
Route::post('v1/public/shop-registration', [PublicController::class, 'shopRegistration']);
Route::post('v1/public/verify-email', [PublicController::class, 'verifyEmail']);
Route::post('v1/public/verify-recovery-code', [PublicController::class, 'verifyRecoveryCode']);
Route::post('v1/public/recover-account', [PublicController::class, 'recoverAccount']);

// Open
Route::post('/open/submit-message', [OpenController::class, 'submitMessage']);
Route::get('/open/order-details-temp/{referenceNumber}', [OpenController::class, 'getOrderDetailsTemp']);
Route::get('/open/get-qr-temp/{referenceNumber}', [OpenController::class, 'getQRTemp']);

// Admin management
Route::post('v1/admin/login', [AdminAuthController::class, 'adminLogin']);
Route::group(['middleware' => 'auth:admin_api', 'abilities:admin:access'], function () {
    Route::post('v1/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('v1/admin/verify-token', [AdminController::class, 'verifyAdmin']);

    // Shop
    Route::get('v1/admin/shop-details/{shopId}', [AdminController::class, 'getShopDetails']);
    Route::post('v1/admin/update-shop/{shopId}', [AdminController::class, 'updateShop']);

    // Branch
    Route::post('v1/admin/save-branch', [AdminController::class, 'saveBranch']);
    Route::get('v1/admin/shop-branches', [AdminController::class, 'getShopBranches']);
    Route::get('v1/admin/branch-details/{branchName}', [AdminController::class, 'getBranchDetails']);
    Route::put('v1/admin/update-branch', [AdminController::class, 'updateBranchDetails']);

    // Orders
    Route::get('v1/admin/orders', [AdminController::class, 'getOrders']);
    Route::get('v1/admin/orders-report', [AdminController::class, 'getOrdersReport']);
    Route::get('v1/admin/total-orders', [AdminController::class, 'getTotalOrdersCount']);
    Route::get('v1/admin/void-orders/{branchId}', [AdminController::class, 'getVoidOrders']); //In frontend, place it to ordersApi
    Route::put('v1/admin/update-void-order/{branch_id}', [AdminController::class, 'updateVoidOrder']); //In frontend, place it to ordersApi

    // Sales
    Route::get('v1/admin/gross-sales-by-date/{branchId}', [AdminController::class, 'getSalesByDateType']); // In frontend, Place it to salesApi
    Route::get('v1/admin/gross-sales-only/{branchId}', [AdminController::class, 'getGrossSalesOnly']);
    Route::get('v1/admin/sales-by-month/{branchId}', [AdminController::class, 'getSalesByMonth']);
    Route::get('v1/admin/total-sales', [AdminController::class, 'getTotalSalesCount']);

    // Products
    Route::post('v1/admin/save-product', [AdminController::class, 'saveProducts']);
    Route::post('v1/admin/save-product-items', [AdminController::class, 'saveProductIngredients']);
    Route::post('v1/admin/update-product/{product_id}', [AdminController::class, 'updateProduct']);
    Route::put('v1/admin/update-product-items/{ingredient_id}', [AdminController::class, 'updateProductItems']);
    Route::get('v1/admin/products', [AdminController::class, 'getProducts']);
    Route::get('v1/admin/products-history', [AdminController::class, 'getProductsHistory']);
    Route::get('v1/admin/product-items/{product_id}', [AdminController::class, 'getProductItems']);
    Route::get('v1/admin/total-products-count/{branchId}', [AdminController::class, 'getTotalProductsCount']);

    // Stocks
    Route::post('v1/admin/save-stock', [AdminController::class, 'saveStock']);
    Route::put('v1/admin/update-stock/{stock_id}', [AdminController::class, 'updateStock']);
    Route::get('v1/admin/stocks', [AdminController::class, 'getStocks']);
    Route::get('v1/admin/stocks-report/{branch_id}', [AdminController::class, 'getStocksReport']);
    Route::get('v1/admin/stocks-history', [AdminController::class, 'getStocksHistory']);
    Route::get('v1/admin/low-stocks/{branch_id}', [AdminController::class, 'getLowStock']);
    Route::get('v1/admin/stocks-only/{branchId}', [AdminController::class, 'getStocksOnly']);

    // Options
    Route::get('v1/admin/void-status', [AdminController::class, 'getVoidStatus']);
    Route::get('v1/admin/ingredients-name/{branch_id}', [AdminController::class, 'getIngredientsName']);
    Route::get('v1/admin/product-temperature-option', [AdminController::class, 'getProductTemperatures']);
    Route::get('v1/admin/product-size-option', [AdminController::class, 'getProductSizes']);
    Route::get('v1/admin/product-category-option', [AdminController::class, 'getProductCategories']);
    Route::get('v1/admin/product-base-category', [AdminController::class, 'getProductBaseCategories']);
    Route::get('v1/admin/product-availability-option', [AdminController::class, 'getAvailabilities']); // to change
    Route::get('v1/admin/product-station-option', [AdminController::class, 'getProductStation']);
    Route::get('v1/admin/unit-option', [AdminController::class, 'getUnits']);
});

// Customer management
Route::post('v1/customer/login', [CustomerAuthController::class, 'customerLogin']);
Route::post('v1/customer/registration', [CustomerController::class, 'customerRegistration']);
Route::post('v1/customer/verify-email', [CustomerController::class, 'verifyEmail']);
Route::group(['middleware' => 'auth:customer_api', 'abilities:customer:access'], function () {
    Route::post('v1/customer/logout', [CustomerAuthController::class, 'logout']);
    Route::get('v1/customer/verify', [CustomerController::class, 'verifyAdmin']);
    Route::get('v1/customer/shops', [CustomerController::class, 'getShops']);
    Route::get('v1/customer/shops-location', [CustomerController::class, 'getShopLocation']);
    Route::get('v1/customer/products', [CustomerController::class, 'getProducts']);
    Route::get('v1/customer/new-products', [CustomerController::class, 'getNewProducts']);
    Route::get('v1/customer/categories-by-new-products', [CustomerController::class, 'getCategoriesByNewProducts']);
    Route::get('v1/customer/products-by-meal-type', [CustomerController::class, 'getProductsByMealType']);
    Route::get('v1/customer/categories-by-meal-type', [CustomerController::class, 'getCategoriesByMealType']);
    Route::get('v1/customer/product-category', [CustomerController::class, 'getProductCategories']);
    Route::get('v1/customer/product-base-category', [CustomerController::class, 'getProductBaseCategories']);
});

// Cashier Management
Route::post('/cashier/login', [CashierAuthController::class, 'cashierLogin']);
Route::group(['middleware' => 'auth:cashier_api', 'abilities:customer:access'], function () {
    Route::post('/cashier/logout', [CashierAuthController::class, 'logout']);
    Route::post('/cashier/submit-transaction', [CashierController::class, 'submitTransaction']);
    Route::post('/cashier/save-void', [CashierController::class, 'saveVoid']);
    Route::put('/cashier/update-order-status', [CashierController::class, 'updateOrderStatus']);
    Route::get('/cashier/current-orders', [CashierController::class, 'getCurrentOrders']);
});

// Kitchen Personnel Management
Route::post('/kitchen/login', [KitchenAuthController::class, 'login']);
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/kitchen/logout', [KitchenAuthController::class, 'logout']);
    Route::put('/kitchen/update-kitchen-product-status', [KitchenController::class, 'updateKitchenProductStatus']);
    Route::put('/kitchen/update-order-status', [KitchenController::class, 'updateOrderStatus']);
    Route::get('/kitchen/current-orders', [KitchenController::class, 'getCurrentOrders']);
    Route::get('/kitchen/kitchen-product-details/{transactionId}', [KitchenController::class, 'getKitchenProductDetails']);
    Route::get('/kitchen/station-status', [KitchenController::class, 'getStationStatus']);
});

// Barista Management
Route::post('/barista/login', [BaristaAuthController::class, 'login']);
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/barista/logout', [BaristaAuthController::class, 'logout']);
    Route::put('/barista/update-barista-product-status', [BaristaController::class, 'updateBaristaProductStatus']); // Unused
    Route::put('/barista/update-order-status', [BaristaController::class, 'updateOrderStatus']);
    Route::get('/barista/current-orders', [BaristaController::class, 'getCurrentOrders']);
    Route::get('/barista/barista-product-details/{transactionId}', [BaristaController::class, 'getBaristaProductDetails']);
    Route::get('/barista/station-status', [BaristaController::class, 'getStationStatus']);
});


// Open
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/open/account-registration', [OpenController::class, 'accountRegistration']);
    Route::get('/open/shop-branches', [OpenController::class, 'getShopBranches']);
    Route::get('/open/branch-details/{branchName}', [OpenController::class, 'getBranchDetails']);
    Route::get('/open/shop-name', [OpenController::class, 'getShopName']);
    Route::get('/open/products', [OpenController::class, 'getProducts']);
    Route::get('/open/stocks/{branch_id}', [OpenController::class, 'getStocks']);
    Route::get('/open/low-stocks/{branch_id}', [OpenController::class, 'getStockNotifQty']);
    Route::get('/open/product-temperature-option', [OpenController::class, 'getProductTemperatures']);
    Route::get('/open/product-size-option', [OpenController::class, 'getProductSizes']);
    Route::get('/open/product-category-option', [OpenController::class, 'getProductCategories']);
    Route::get('/open/product-availability-option', [OpenController::class, 'getProductAvailabilities']);
    Route::get('/open/order-status', [OpenController::class, 'getOrderStatus']);
    Route::get('/open/order-details/{referenceNumber}', [OpenController::class, 'getOrderDetails']);
    Route::get('/open/void-orders', [OpenController::class, 'getVoid']);
    Route::get('/open/get-qr/{referenceNumber}', [OpenController::class, 'getQR']);
});

// Payment Gateway Group
Route::prefix('paymongo')->group(function () {
    Route::post('/generate-qr', [PaymentController::class, 'generatingQRCode']);
    Route::post('/webhook/paymongo', [PaymentController::class, 'handlePayment']);
});

// Dev
Route::post('/dev/login', [DevController::class, 'login']);
Route::post('/dev/registration', function (Request $request) {
    $validated = $request->validate([
        'dev_name' => 'required|string|max:191',
        'dev_email' => 'required|string|email|max:191|unique:tbl_dev,dev_email',
        'dev_password' => 'required|string|min:8',
    ]);
    DB::beginTransaction();
    try {
        $dev = DevModel::create([
            'dev_name' => $validated['dev_name'],
            'dev_email' => $validated['dev_email'],
            'dev_password' => Hash::make($validated['dev_password']),
        ]);
        $token = $dev->createToken('auth-token')->plainTextToken;
        DB::commit();
        return response()->json([
            'message' => 'Developer registration successful',
            'data' => $dev,
            'token' => $token,
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Registration failed!',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/dev/logout', [DevController::class, 'logout']);
    Route::post('/dev/save-shop', [DevController::class, 'saveShop']);
    Route::get('/dev/shops', [DevController::class, 'getShops']);
    Route::get('/dev/shop-branches/{shop_id}', [DevController::class, 'getShopBranches']);
});
