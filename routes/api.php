<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CashierAuthController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\KitchenAuthController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\BaristaAuthController;
use App\Http\Controllers\BaristaController;
use App\Http\Controllers\OpenController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymongoWebhookController;
use App\Models\ShopModel;
use App\Models\BranchModel;
use App\Models\AdminModel;
use App\Models\CashierModel;
use App\Models\KitchenPersonnelModel;
use App\Models\BaristaModel;
use App\Models\DevModel;

// DEVELOPER
Route::post('/dev/login', [DevController::class, 'login']);
Route::middleware('auth:sanctum')->post('/dev/logout', [DevController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/dev/save-shop', [DevController::class, 'saveShop']);
Route::middleware('auth:sanctum')->get('/dev/shops', [DevController::class, 'getShops']);
Route::middleware('auth:sanctum')->get('/dev/shop-branches/{shop_id}', [DevController::class, 'getShopBranches']);
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

// ADMIN
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/admin/logout', [AdminAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/admin/save-branch', [AdminController::class, 'saveBranch']);
Route::middleware('auth:sanctum')->post('/admin/save-stock', [AdminController::class, 'saveStock']);
Route::middleware('auth:sanctum')->post('/admin/save-product', [AdminController::class, 'saveProduct']);
Route::middleware('auth:sanctum')->post('/admin/save-product-items', [AdminController::class, 'saveProductIngredients']);
Route::middleware('auth:sanctum')->put('/admin/update-stock/{stock_id}', [AdminController::class, 'updateStock']);
Route::middleware('auth:sanctum')->put('/admin/update-product/{product_id}', [AdminController::class, 'updateProduct']);
Route::middleware('auth:sanctum')->put('/admin/update-product-items/{ingredient_id}', [AdminController::class, 'updateProductItems']);
Route::middleware('auth:sanctum')->put('/admin/update-void/{branch_id}', [AdminController::class, 'updateVoidOrder']);
Route::middleware('auth:sanctum')->get('/admin/shop-branches', [AdminController::class, 'getShopBranches']);
Route::middleware('auth:sanctum')->get('/admin/branch-details/{branchName}', [AdminController::class, 'getBranchDetails']);
Route::middleware('auth:sanctum')->get('/admin/products/{branch_id}', [AdminController::class, 'getProducts']);
Route::middleware('auth:sanctum')->get('/admin/products-history/{branch_id}', [AdminController::class, 'getProductsHistory']);
Route::middleware('auth:sanctum')->get('/admin/product-items/{product_id}', [AdminController::class, 'getProductItems']);
Route::middleware('auth:sanctum')->get('/admin/total-products-count/{branchId}', [AdminController::class, 'getTotalProductsCount']);
Route::middleware('auth:sanctum')->get('/admin/stocks/{branch_id}', [AdminController::class, 'getStocks']);
Route::middleware('auth:sanctum')->get('/admin/stocks-report/{branch_id}', [AdminController::class, 'getStocksReport']);
Route::middleware('auth:sanctum')->get('/admin/ingredients-name/{branch_id}/{ingredient_id}', [AdminController::class, 'getIngredientsNameBasedId']);
Route::middleware('auth:sanctum')->get('/admin/stocks-name-only/{branch_id}', [AdminController::class, 'getStocksList']);
Route::middleware('auth:sanctum')->get('/admin/stocks-history/{branch_id}', [AdminController::class, 'getStocksHistory']);
Route::middleware('auth:sanctum')->get('/admin/low-stocks/{branch_id}', [AdminController::class, 'getLowStock']);
// Route::middleware('auth:sanctum')->get('/admin/{branchId}/low-stocks', [AdminController::class, 'getLowStock']);
Route::middleware('auth:sanctum')->get('/admin/all-orders/{branchId}', [AdminController::class, 'getOrdersByDateType']);
Route::middleware('auth:sanctum')->get('/admin/gross-sales-by-date/{branchId}', [AdminController::class, 'getSalesByDateType']);
Route::middleware('auth:sanctum')->get('/admin/gross-sales-only/{branchId}', [AdminController::class, 'getGrossSalesOnly']);
Route::middleware('auth:sanctum')->get('/admin/sales-by-month/{branchId}', [AdminController::class, 'getSalesByMonth']);
Route::middleware('auth:sanctum')->get('/admin/orders-only/{branchId}', [AdminController::class, 'getOrdersOnly']);
Route::middleware('auth:sanctum')->get('/admin/stocks-only/{branchId}', [AdminController::class, 'getStocksOnly']);
Route::middleware('auth:sanctum')->get('/admin/void-orders/{branchId}', [AdminController::class, 'getVoidOrders']);
Route::middleware('auth:sanctum')->get('/admin/void-status', [AdminController::class, 'getVoidStatus']);
Route::middleware('auth:sanctum')->get('/admin/product-temperature-option', [AdminController::class, 'getProductTemperatures']);
Route::middleware('auth:sanctum')->get('/admin/product-size-option', [AdminController::class, 'getProductSizes']);
Route::middleware('auth:sanctum')->get('/admin/product-category-option', [AdminController::class, 'getProductCategories']);
Route::middleware('auth:sanctum')->get('/admin/product-availability-option', [AdminController::class, 'getAvailabilities']); // to change
Route::middleware('auth:sanctum')->get('/admin/product-station-option', [AdminController::class, 'getProductStation']);
Route::middleware('auth:sanctum')->get('/admin/stock-unit-option', [AdminController::class, 'getStockUnits']);

// CASHIER
Route::post('/cashier/login', [CashierAuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/cashier/logout', [CashierAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/cashier/current-orders', [CashierController::class, 'getCurrentOrders']);
Route::middleware('auth:sanctum')->put('/cashier/update-order-status', [CashierController::class, 'updateOrderStatus']);
Route::middleware('auth:sanctum')->post('/cashier/submit-transaction', [CashierController::class, 'submitTransaction']);
Route::middleware('auth:sanctum')->post('/cashier/save-void', [CashierController::class, 'saveVoid']);
Route::prefix('paymongo')->group(function () {
    Route::post('/generate-qr', [PaymentController::class, 'generateQr']);
    Route::post('/payment-intents', [PaymentController::class, 'store']);
    Route::post('/payment-intents/attach', [PaymentController::class, 'attach']);
    Route::get('/payment-intents/{intentId}/status', [PaymentController::class, 'checkStatus']);
    Route::post('/webhook/{payment_intent_id}', [PaymongoWebhookController::class, 'handle']);
});

// KITCHEN
Route::post('/kitchen/login', [KitchenAuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/kitchen/logout', [KitchenAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/kitchen/current-orders', [KitchenController::class, 'getCurrentOrders']);
Route::middleware('auth:sanctum')->get('/kitchen/kitchen-product-details/{transactionId}', [KitchenController::class, 'getKitchenProductDetails']);
Route::middleware('auth:sanctum')->get('/kitchen/station-status', [KitchenController::class, 'getStationStatus']);
Route::middleware('auth:sanctum')->put('/kitchen/update-kitchen-product-status', [KitchenController::class, 'updateKitchenProductStatus']);
Route::middleware('auth:sanctum')->put('/kitchen/update-order-status', [KitchenController::class, 'updateOrderStatus']);

// BARISTA
Route::post('/barista/login', [BaristaAuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/barista/logout', [BaristaAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/barista/current-orders', [BaristaController::class, 'getCurrentOrders']);
Route::middleware('auth:sanctum')->get('/barista/barista-product-details/{transactionId}', [BaristaController::class, 'getBaristaProductDetails']);
Route::middleware('auth:sanctum')->get('/barista/station-status', [BaristaController::class, 'getStationStatus']);
Route::middleware('auth:sanctum')->put('/barista/update-barista-product-status', [BaristaController::class, 'updateBaristaProductStatus']); // Unused
Route::middleware('auth:sanctum')->put('/barista/update-order-status', [BaristaController::class, 'updateOrderStatus']);

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
Route::post('/open/submit-message', [OpenController::class, 'submitMessage']);
Route::post('/open/save-shop', [OpenController::class, 'saveShop']);
Route::get('/open/order-details-temp/{referenceNumber}', [OpenController::class, 'getOrderDetailsTemp']);
Route::get('/open/get-qr-temp/{referenceNumber}', [OpenController::class, 'getQRTemp']);

Route::post('/registerAccount', function (Request $request) {
    $validated = $request->validate([
        'shop_name' => 'required|string|max:50',
        'shop_owner' => 'required|string|max:50',
        'shop_address' => 'required|string',
        'shop_email' => 'required|string|email|max:50|unique:tbl_shops,shop_email',
        'shop_contact_number' => 'required|string|max:13',
        'branch_name' => 'required|string|max:50',
        'branch_address' => 'required|string',
        'branch_manager_name' => 'required|string|max:50',
        'branch_contact_number' => 'required|string|max:13',
        'admin_name' => 'required|string|max:255',
        'admin_email' => 'required|string|email|max:255|unique:tbl_admin,admin_email',
        'admin_password' => 'required|string|min:8',
        'admin_mpin' => 'required|numeric',
        'cashier_name' => 'required|string|max:255',
        'cashier_email' => 'required|string|email|max:255|unique:tbl_cashier,cashier_email',
        'cashier_password' => 'required|string|min:8',
        'cashier_mpin' => 'required|numeric',
        'kitchen_personnel_name' => 'required|string|max:255',
        'kitchen_personnel_email' => 'required|string|email|max:255|unique:tbl_kitchen_personnel,kitchen_personnel_email',
        'kitchen_personnel_password' => 'required|string|min:8',
        'kitchen_personnel_mpin' => 'required|numeric',
        'barista_name' => 'required|string|max:255',
        'barista_email' => 'required|string|email|max:255|unique:tbl_barista,barista_email',
        'barista_password' => 'required|string|min:8',
        'barista_mpin' => 'required|numeric',
    ]);

    DB::beginTransaction();

    try {
        $shop = ShopModel::create([
            'shop_name' => $validated['shop_name'],
            'shop_owner' => $validated['shop_owner'],
            'shop_address' => $validated['shop_address'],
            'shop_email' => $validated['shop_email'],
            'shop_contact_number' => $validated['shop_contact_number'],
        ]);

        $branch = BranchModel::create([
            'branch_name' => $validated['branch_name'],
            'branch_address' => $validated['branch_address'],
            'branch_manager_name' => $validated['branch_manager_name'],
            'branch_contact_number' => $validated['branch_contact_number'],
            'shop_id' => $shop->shop_id,
        ]);

        $admin = AdminModel::create([
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => Hash::make($validated['admin_password']),
            'admin_mpin' => Hash::make($validated['admin_mpin']),
            'shop_id' => $shop->shop_id,
        ]);
        $cashier = CashierModel::create([
            'cashier_name' => $validated['cashier_name'],
            'cashier_email' => $validated['cashier_email'],
            'cashier_password' => Hash::make($validated['cashier_password']),
            'cashier_mpin' => $validated['cashier_mpin'],
            'cashier_mpin' => Hash::make($validated['cashier_mpin']),
            'shop_id' => $shop->shop_id,
            'branch_id' => $branch->branch_id,

        ]);
        $kitchen = KitchenPersonnelModel::create([
            'kitchen_personnel_name' => $validated['kitchen_personnel_name'],
            'kitchen_personnel_email' => $validated['kitchen_personnel_email'],
            'kitchen_personnel_password' => Hash::make($validated['kitchen_personnel_password']),
            'kitchen_personnel_mpin' => Hash::make($validated['kitchen_personnel_mpin']),
            'shop_id' => $shop->shop_id,
            'branch_id' => $branch->branch_id,
        ]);
        $barista = BaristaModel::create([
            'barista_name' => $validated['barista_name'],
            'barista_email' => $validated['barista_email'],
            'barista_password' => Hash::make($validated['barista_password']),
            'barista_mpin' => Hash::make($validated['barista_mpin']),
            'shop_id' => $shop->shop_id,
            'branch_id' => $branch->branch_id,
        ]);
        $token = $admin->createToken('auth-token')->plainTextToken;

        DB::commit();

        return response()->json([
            'message' => 'Registration successful',
            'shop' => $shop,
            'branch' => $branch,
            'admin' => $admin->makeHidden(['admin_password', 'admin_mpin']),
            'cashier' => $cashier->makeHidden(['cashier_password', 'cashier_mpin']),
            'kitchen' => $kitchen->makeHidden(['kitchen_personnel_password', 'kitchen_personnel_mpin']),
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
