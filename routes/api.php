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

// Customer management
Route::post('v1/customer/login', [CustomerAuthController::class, 'customerLogin']);
Route::post('v1/customer/registration', [CustomerController::class, 'customerRegistration']);
Route::post('v1/customer/verify-email', [CustomerController::class, 'verifyEmail']);

Route::group(['middleware' => 'auth:customer_api'], function () {
    Route::post('v1/customer/logout', [CustomerAuthController::class, 'logout']);
    Route::get('v1/customer/shops', [PublicController::class, 'getShops']);
    Route::get('v1/customer/shops-location', [PublicController::class, 'getShopLocation']);
    Route::get('v1/customer/products', [PublicController::class, 'getProducts']);
    Route::get('v1/customer/new-products', [PublicController::class, 'getNewProducts']);
    Route::get('v1/customer/categories-by-new-products', [PublicController::class, 'getCategoriesByNewProducts']);
    Route::get('v1/customer/products-by-meal-type', [PublicController::class, 'getProductsByMealType']);
    Route::get('v1/customer/categories-by-meal-type', [PublicController::class, 'getCategoriesByMealType']);
    Route::get('v1/customer/product-category', [PublicController::class, 'getProductCategories']);
    Route::get('v1/customer/product-base-category', [PublicController::class, 'getProductBaseCategories']);
});

// Public
Route::post('v1/public/shop-registration', [PublicController::class, 'shopRegistration']);
Route::post('v1/public/verify-email', [PublicController::class, 'verifyEmail']);
Route::post('v1/public/verify-recovery-code', [PublicController::class, 'verifyRecoveryCode']);
Route::post('v1/public/recover-account', [PublicController::class, 'recoverAccount']);
Route::get('v1/public/shops-without-products', [PublicController::class, 'getShopsWithoutProducts']);
Route::get('v1/public/shops', [PublicController::class, 'getShops']);
Route::get('v1/public/shops-location', [PublicController::class, 'getShopLocation']);
Route::get('v1/public/products', [PublicController::class, 'getProducts']);
Route::get('v1/public/new-products', [PublicController::class, 'getNewProducts']);
Route::get('v1/public/products-by-meal-type', [PublicController::class, 'getProductsByMealType']);
Route::get('v1/public/categories-by-new-products', [PublicController::class, 'getCategoriesByNewProducts']);
Route::get('v1/public/categories-by-meal-type', [PublicController::class, 'getCategoriesByMealType']);
Route::get('v1/public/product-category', [PublicController::class, 'getProductCategories']);
Route::get('v1/public/product-base-category', [PublicController::class, 'getProductBaseCategories']);

//Route::get('v1/public/all-shops', [PublicController::class, 'getAllPublicShops']); // new structure
//Route::get('v1/public/all-products', [PublicController::class, 'getAllPublicProductsFromShop']); // new structure
//Route::get('v1/public/new-products', [PublicController::class, 'getAllNewPublicProducts']); // new structure
//Route::get('v1/public/products-by-meal-type', [PublicController::class, 'getAllPublicProductsByMealType']); // new structure
//Route::get('v1/public/categories-by-new-products', [PublicController::class, 'getAllCategoriesByNewProducts']); // new structure
//Route::get('v1/public/categories-by-meal-type', [PublicController::class, 'getAllCategoriesByMealType']); // new structure
//Route::get('v1/public/product-category', [PublicController::class, 'getAllProductCategories']);

// Login and others
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/cashier/login', [CashierAuthController::class, 'login']);
Route::post('/kitchen/login', [KitchenAuthController::class, 'login']);
Route::post('/barista/login', [BaristaAuthController::class, 'login']);
Route::post('/open/submit-message', [OpenController::class, 'submitMessage']);
Route::get('/open/order-details-temp/{referenceNumber}', [OpenController::class, 'getOrderDetailsTemp']);
Route::get('/open/get-qr-temp/{referenceNumber}', [OpenController::class, 'getQRTemp']);

// Admin management
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);

    // Branch
    Route::post('/admin/save-branch', [AdminController::class, 'saveBranch']);
    Route::get('/admin/shop-branches', [AdminController::class, 'getShopBranches']);
    Route::get('/admin/branch-details/{branchName}', [AdminController::class, 'getBranchDetails']);

    // Orders
    Route::get('/admin/orders', [AdminController::class, 'getOrders']);
    Route::get('/admin/orders-report', [AdminController::class, 'getOrdersReport']);
    Route::get('/admin/total-orders', [AdminController::class, 'getTotalOrdersCount']);
    Route::get('/admin/void-orders/{branchId}', [AdminController::class, 'getVoidOrders']);
    Route::put('/admin/update-void/{branch_id}', [AdminController::class, 'updateVoidOrder']);

    // Sales
    Route::get('/admin/gross-sales-by-date/{branchId}', [AdminController::class, 'getSalesByDateType']);
    Route::get('/admin/gross-sales-only/{branchId}', [AdminController::class, 'getGrossSalesOnly']);
    Route::get('/admin/sales-by-month/{branchId}', [AdminController::class, 'getSalesByMonth']);
    Route::get('/admin/total-sales', [AdminController::class, 'getTotalSalesCount']);

    // Products
    Route::post('/admin/save-product', [AdminController::class, 'saveProducts']);
    Route::post('/admin/save-product-items', [AdminController::class, 'saveProductIngredients']);
    Route::get('/admin/products', [AdminController::class, 'getProducts']);
    Route::get('/admin/products-history', [AdminController::class, 'getProductsHistory']);
    Route::get('/admin/product-items/{product_id}', [AdminController::class, 'getProductItems']);
    Route::get('/admin/total-products-count/{branchId}', [AdminController::class, 'getTotalProductsCount']);
    Route::put('/admin/update-product/{product_id}', [AdminController::class, 'updateProduct']);
    Route::put('/admin/update-product-items/{ingredient_id}', [AdminController::class, 'updateProductItems']);

    // Stocks
    Route::post('/admin/save-stock', [AdminController::class, 'saveStock']);
    Route::get('/admin/ingredients-name/{branch_id}', [AdminController::class, 'getIngredientsName']);
    Route::get('/admin/stocks', [AdminController::class, 'getStocks']);
    Route::get('/admin/stocks-report/{branch_id}', [AdminController::class, 'getStocksReport']);
    Route::get('/admin/stocks-history', [AdminController::class, 'getStocksHistory']);
    Route::get('/admin/low-stocks/{branch_id}', [AdminController::class, 'getLowStock']);
    Route::get('/admin/stocks-only/{branchId}', [AdminController::class, 'getStocksOnly']);
    Route::put('/admin/update-stock/{stock_id}', [AdminController::class, 'updateStock']);
    // Route::get('/admin/{branchId}/low-stocks', [AdminController::class, 'getLowStock']);

    // Options
    Route::get('/admin/void-status', [AdminController::class, 'getVoidStatus']);
    Route::get('/admin/product-temperature-option', [AdminController::class, 'getProductTemperatures']);
    Route::get('/admin/product-size-option', [AdminController::class, 'getProductSizes']);
    Route::get('/admin/product-category-option', [AdminController::class, 'getProductCategories']);
    Route::get('/admin/product-availability-option', [AdminController::class, 'getAvailabilities']); // to change
    Route::get('/admin/product-station-option', [AdminController::class, 'getProductStation']);
    Route::get('/admin/unit-option', [AdminController::class, 'getUnits']);
});

// Employees (Cashier, Kitchen Personnel, and Barista)
Route::group(['middleware' => 'auth:sanctum'], function () {
    // CASHIER
    Route::post('/cashier/logout', [CashierAuthController::class, 'logout']);
    Route::post('/cashier/submit-transaction', [CashierController::class, 'submitTransaction']);
    Route::post('/cashier/save-void', [CashierController::class, 'saveVoid']);
    Route::get('/cashier/current-orders', [CashierController::class, 'getCurrentOrders']);
    Route::put('/cashier/update-order-status', [CashierController::class, 'updateOrderStatus']);

    // KITCHEN PERSONNEL
    Route::post('/kitchen/logout', [KitchenAuthController::class, 'logout']);
    Route::get('/kitchen/current-orders', [KitchenController::class, 'getCurrentOrders']);
    Route::get('/kitchen/kitchen-product-details/{transactionId}', [KitchenController::class, 'getKitchenProductDetails']);
    Route::get('/kitchen/station-status', [KitchenController::class, 'getStationStatus']);
    Route::put('/kitchen/update-kitchen-product-status', [KitchenController::class, 'updateKitchenProductStatus']);
    Route::put('/kitchen/update-order-status', [KitchenController::class, 'updateOrderStatus']);

    // BARISTA
    Route::post('/barista/logout', [BaristaAuthController::class, 'logout']);
    Route::get('/barista/current-orders', [BaristaController::class, 'getCurrentOrders']);
    Route::get('/barista/barista-product-details/{transactionId}', [BaristaController::class, 'getBaristaProductDetails']);
    Route::get('/barista/station-status', [BaristaController::class, 'getStationStatus']);
    Route::put('/barista/update-barista-product-status', [BaristaController::class, 'updateBaristaProductStatus']); // Unused
    Route::put('/barista/update-order-status', [BaristaController::class, 'updateOrderStatus']);

    // OPEN
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
