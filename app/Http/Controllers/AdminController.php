<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\BranchModel;
use App\Models\ProductIngredientsModel;
use App\Models\ProductsModel;
use App\Models\ProductsHistoryModel;
use App\Models\StocksHistoryModel;
use App\Models\StocksModel;
use App\Models\StockBatchesModel;
use App\Models\TemperatureModel;
use App\Models\SizeModel;
use App\Models\CategoryModel;
use App\Models\AvailabilityModel;
use App\Models\UnitModel;
use App\Models\StationModel;
use App\Models\OrdersModel;
use App\Models\OrderItemsModel;
use App\Models\VoidOrdersModel;
use App\Models\VoidStatusModel;
Use App\Models\SalesModel;
Use App\Models\IngredientsModel;

class AdminController extends Controller
{

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
            $shopId = $request->user()->shop_id;
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

                $stocks = StocksModel::where('branch_id', $newBranchId)->get();
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

                $ingredients = ProductIngredientsModel::where('branch_id', $newBranchId)->get();
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
            $shopId = auth()->user()->shop_id;
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
            $shopId = auth()->user()->shop_id;
            $branchData = BranchModel::select(
                'tbl_shops.shop_name',
                'tbl_shop_branch.shop_id',
                'tbl_shop_branch.branch_id',
                'tbl_shop_branch.branch_name',
                'tbl_shop_branch.branch_address',
                'tbl_shop_branch.branch_manager_name',
                'tbl_shop_branch.branch_contact_number',
                'tbl_admin.admin_name',
                'tbl_cashier.cashier_name',
                'tbl_cashier.cashier_email',
                'tbl_barista.barista_name',
                'tbl_barista.barista_email',
                'tbl_kitchen_personnel.kitchen_personnel_name',
                'tbl_kitchen_personnel.kitchen_personnel_email',
            )
                ->join('tbl_shops', 'tbl_shop_branch.shop_id', '=', 'tbl_shops.shop_id')
                ->join('tbl_admin', 'tbl_shops.shop_id', '=', 'tbl_admin.shop_id')
                ->join('tbl_cashier', 'tbl_shops.shop_id', '=', 'tbl_cashier.shop_id')
                ->join('tbl_barista', 'tbl_shops.shop_id', '=', 'tbl_barista.shop_id')
                ->join('tbl_kitchen_personnel', 'tbl_shops.shop_id', '=', 'tbl_kitchen_personnel.shop_id')
                ->where('tbl_shop_branch.branch_name', urldecode($branchName))
                ->where('tbl_shop_branch.shop_id', $shopId)
                ->first();
            if ($branchData && $branchData->branch_address) {
                $branchData->branch_address = str_replace(',', '', $branchData->branch_address);
            }
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

    /**** Products ****/

    public function saveProduct(Request $request)
    {
        $request->validate([
            '*.product_name' => 'required|string',
            '*.base_price' => 'required|numeric',
            '*.product_temp_id' => 'required|integer',
            '*.product_size_id' => 'required|integer',
            '*.product_category_id' => 'required|integer',
            '*.station_id' => 'required|integer',
            '*.branch_id' => 'required|integer',
        ]);

        try {
            foreach ($request->all() as $item) {
                $shopId = $request->user()->shop_id;
                $userId = $request->user()->admin_id;
                $product = new ProductsModel();
                $product->product_name = $item['product_name'];
                $product->base_price = $item['base_price'];
                $product->product_temp_id = $item['product_temp_id'];
                $product->product_size_id = $item['product_size_id'];
                $product->product_category_id = $item['product_category_id'];
                $product->station_id = $item['station_id'];
                $product->availability_id = 2;
                $product->shop_id = $shopId;
                $product->branch_id = $item['branch_id'];
                $product->shop_id = $shopId;
                $product->user_id = $userId;
                $product->created_at = now();
                $product->updated_at = now();
                $product->save();
                $newProductId = $product->product_id;
                $shopId = $product->shop_id;
                $branchId = $product->branch_id;
                $userId = $product->user_id;
                ProductsHistoryModel::create([
                    'product_id' => $newProductId,
                    'manage_id' => 1, // SAVE
                    'description' => 'New Product Saved',
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Product saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error saving products!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProduct(Request $request, $productId)
    {
        $validated = $request->validate([
            'availability_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'product_category_id' => 'required|integer',
            'product_id' => 'required|integer',
            'product_name' => 'required|string',
            'base_price' => 'required|numeric',
            'product_size_id' => 'required|integer',
            'product_temp_id' => 'required|integer',
            'shop_id' => 'required|integer',
        ]);

        try {
            $userId = auth()->user()->admin_id;
            $branchId = $validated['branch_id'];
            $product = ProductsModel::findOrFail($productId);
            $originalValues = $product->getOriginal();
            if ($validated['availability_id'] == 1 && $originalValues['availability_id'] != 1) {
                $ingredientStockIds = ProductIngredientsModel::where('product_id', $productId)
                    ->where('branch_id', $branchId)
                    ->pluck('ingredient_id')
                    ->toArray();
                if (!empty($ingredientStockIds)) {
                    $unavailableStocks = StocksModel::whereIn('ingredient_id', $ingredientStockIds)
                        ->where('availability_id', '!=', 1)
                        ->where('branch_id', $branchId)
                        ->exists();
                    if ($unavailableStocks) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Cannot set product to available because some required ingredients are not available in stock',
                        ], 400);
                    }
                }
            }
            $product->update($validated);
            $product->load(['temperature', 'category', 'availability']);
            $newProductId = $product->product_id;
            $shopId = $product->shop_id;
            $branchId = $product->branch_id;
            $changes = [];
            foreach ($validated as $key => $value) {
                if ($originalValues[$key] != $value) {
                    $changes[$key] = [
                        'from' => $originalValues[$key],
                        'to' => $value
                    ];
                }
            }
            $description = '';
            foreach ($changes as $field => $change) {
                if ($field === 'base_price') {
                    $description .= "Price: From [₱{$change['from']}] To [₱{$change['to']}]. ";
                } elseif ($field === 'availability_id') {
                    $fromAvailability = AvailabilityModel::find($change['from']);
                    $toAvailability = AvailabilityModel::find($change['to']);
                    $fromLabel = $fromAvailability ? $fromAvailability->availability_label : $change['from'];
                    $toLabel = $toAvailability ? $toAvailability->availability_label : $change['to'];
                    $description .= "Availability: From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'product_temp_id') {
                    $fromTemp = TemperatureModel::find($change['from']);
                    $toTemp = TemperatureModel::find($change['to']);
                    $fromLabel = $fromTemp ? $fromTemp->temp_label : $change['from'];
                    $toLabel = $toTemp ? $toTemp->temp_label : $change['to'];
                    $description .= "Temperature: From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'product_size_id') {
                    $fromSize = SizeModel::find($change['from']);
                    $toTemp = SizeModel::find($change['to']);
                    $fromLabel = $fromSize ? $fromSize->size_label : $change['from'];
                    $toLabel = $toTemp ? $toTemp->size_label : $change['to'];
                    $description .= "Size: From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'product_category_id') {
                    $fromCategory = CategoryModel::find($change['from']);
                    $toCategory = CategoryModel::find($change['to']);
                    $fromLabel = $fromCategory ? $fromCategory->category_label : $change['from'];
                    $toLabel = $toCategory ? $toCategory->category_label : $change['to'];
                    $description .= "Category: From [{$fromLabel}] To [{$toLabel}]. ";
                } else {
                    $description .= ucfirst(str_replace('_', ' ', $field)) . ": From [{$change['from']}] To [{$change['to']}]. ";
                }
            }
            if (empty($description)) {
                $description = 'No fields were updated';
            }
            ProductsHistoryModel::create([
                'product_id' => $newProductId,
                'manage_id' => 2, // UPDATE
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'description' => trim($description),
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Product updated successfully',
                'updated_at' => now('Asia/Manila')->toDateTimeString(),
                'changes' => $changes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating product!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getProducts($branchId)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $data = ProductsModel::select(
                'tbl_products.product_id',
                'tbl_products.product_name',
                'tbl_products.base_price',
                'tbl_products.temp_id',
                'tbl_products.size_id',
                'tbl_products.updated_at',
                'tbl_products.category_id',
                'tbl_products.availability_id',
                'tbl_product_temp.temp_label',
                'tbl_product_size.size_label',
                'tbl_product_category.category_label',
                'tbl_availability.availability_label',
                'tbl_products.branch_id',
                'tbl_products.shop_id',
            )
                ->join('tbl_product_temp', 'tbl_products.temp_id', '=', 'tbl_product_temp.product_temp_id')
                ->join('tbl_product_size', 'tbl_products.size_id', '=', 'tbl_product_size.product_size_id')
                ->join('tbl_product_category', 'tbl_products.category_id', '=', 'tbl_product_category.product_category_id')
                ->join('tbl_availability', 'tbl_products.availability_id', '=', 'tbl_availability.availability_id')
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.branch_id', $branchId)
                ->orderByDesc('tbl_products.updated_at')
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No products found!' : 'Products fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching products!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductAlone($product_id)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $data = ProductsModel::select(
                'tbl_products.product_id',
                'tbl_products.product_name',
                'tbl_products.base_price',
                'tbl_products.product_temp_id',
                'tbl_products.product_size_id',
                'tbl_products.updated_at',
                'tbl_products.product_category_id',
                'tbl_products.availability_id',
                'tbl_product_temp.temp_label',
                'tbl_product_size.size_label',
                'tbl_category.category_label',
                'tbl_availability.availability_label',
                'tbl_products.branch_id',
                'tbl_products.shop_id',
            )
                ->join('tbl_product_temp', 'tbl_products.product_temp_id', '=', 'tbl_product_temp.temp_id')
                ->join('tbl_product_size', 'tbl_products.product_size_id', '=', 'tbl_product_size.size_id')
                ->join('tbl_category', 'tbl_products.product_category_id', '=', 'tbl_category.category_id')
                ->join('tbl_availability', 'tbl_products.availability_id', '=', 'tbl_availability.availability_id')
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.product_id', $product_id)
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No product found!' : 'Product alone fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching products!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductsHistory($branchId)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $data = ProductsHistoryModel::select(
                'tbl_products.product_name',
                'tbl_products_history.manage_id',
                'tbl_products_history.description',
                'tbl_admin.admin_name',
                'tbl_products_history.updated_at',
            )
                ->join('tbl_products', 'tbl_products_history.product_id', '=', 'tbl_products.product_id')
                ->join('tbl_admin', 'tbl_products_history.user_id', '=', 'tbl_admin.admin_id')
                ->where('tbl_products_history.branch_id', $branchId)
                ->where('tbl_products_history.shop_id', $shopId)
                ->orderBy('tbl_products_history.updated_at')
                ->get();
            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No products found!' : 'Products history fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching products!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductsOnly($branchId)
    {
        // For Dashboard
        try {
            $shopId = auth()->user()->shop_id;
            $totalProducts = ProductsModel::select(
                DB::raw('COUNT(tbl_products.product_id) as total_products')
            )
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.branch_id', $branchId)
                ->first();
            return response()->json([
                'status' => true,
                'message' => 'Total orders fetched successfully!',
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

    public function saveProductIngredients(Request $request)
    {
        $shopId = auth()->user()->shop_id;
        $request->validate([
            '*.shop_id' => 'required|integer',
            '*.branch_id' => 'required|integer',
            '*.product_id' => 'required|integer',
            '*.ingredient_id' => 'required|integer',
            '*.unit_usage' => 'required|numeric',
            '*.ingredient_capital' => 'required|numeric',
        ]);
        try {
            foreach ($request->all() as $item) {
                ProductIngredientsModel::create([
                    'product_id' => $item['product_id'],
                    'unit_usage' => $item['unit_usage'],
                    'ingredient_capital' => $item['ingredient_capital'],
                    'ingredient_id' => $item['ingredient_id'],
                    'shop_id' => $shopId,
                    'branch_id' => $item['branch_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Product ingredients saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error saving product ingredients!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProductIngredients(Request $request, $ingredient_id)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'ingredient_id' => 'required|integer',
            'unit_usage' => 'required|numeric',
            'ingredient_capital' => 'required|numeric',
        ]);
        try {
            $ingredient = ProductIngredientsModel::findOrFail($ingredient_id);
            $originalValues = $ingredient->getOriginal();
            $ingredient->update($validated);
            $ingredient->load(['product', 'stock']);
            $newProductId = $ingredient->product_id;
            $shopId = $ingredient->shop_id;
            $branchId = $ingredient->branch_id;
            $userId = auth()->user()->admin_id;
            $changes = [];
            foreach ($validated as $key => $value) {
                if ($originalValues[$key] != $value) {
                    $changes[$key] = [
                        'from' => $originalValues[$key],
                        'to' => $value
                    ];
                }
            }
            $description = '';
            foreach ($changes as $field => $change) {
                if ($field === 'ingredient_id') {
                    $from = StocksModel::find($change['from']);
                    $to = StocksModel::find($change['to']);
                    $fromLabel = $from ? $from->ingredient_name : $change['from'];
                    $toLabel = $to ? $to->ingredient_name : $change['to'];
                    $description .= "Stock ingredient: From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'ingredient_capital') {
                    $description .= "Ingredient capital: From [₱{$change['from']}] To [₱{$change['to']}]. ";
                } else {
                    $description .= ucfirst(str_replace('_', ' ', $field)) . ": From [{$change['from']}] To [{$change['to']}]. ";
                }
            }

            if (empty($description)) {
                $description = 'No fields were updated';
            }

            ProductsHistoryModel::create([
                'product_id' => $newProductId,
                'manage_id' => 2, // UPDATE
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'description' => trim($description),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Product updated successfully',
                'updated_at' => now('Asia/Manila')->toDateTimeString(),
                'changes' => $changes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating product!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getIngredientsByProduct($product_id)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $data = ProductIngredientsModel::select(
                'tbl_product_items.product_ingredient_id',
                'tbl_product_items.product_id',
                'tbl_product_items.unit_usage',
                'tbl_product_items.ingredient_capital',
                'tbl_product_items.updated_at',
                'tbl_products.product_name',
                'tbl_product_temp.temp_label',
                'tbl_product_size.size_label',
                'tbl_stocks.ingredient_id',
                'tbl_stocks.branch_id',
                'tbl_stocks.ingredient_name',
                'tbl_availability.availability_label',
                'tbl_ingredient_unit.unit_avb',
            )
                ->join('tbl_products', 'tbl_product_items.product_id', '=', 'tbl_products.product_id')
                ->join('tbl_product_temp', 'tbl_products.product_temp_id', '=', 'tbl_product_temp.temp_id')
                ->join('tbl_product_size', 'tbl_products.product_size_id', '=', 'tbl_product_size.size_id')
                ->join('tbl_stocks', 'tbl_product_items.ingredient_id', '=', 'tbl_stocks.ingredient_id')
                ->join('tbl_availability', 'tbl_stocks.availability_id', '=', 'tbl_availability.availability_id')
                ->join('tbl_ingredient_unit', 'tbl_stocks.stock_unit', '=', 'tbl_ingredient_unit.unit_id')
                ->where('tbl_product_items.shop_id', $shopId)
                ->where('tbl_product_items.product_id', $product_id)
                ->orderBy('tbl_stocks.ingredient_name')
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No product ingredients found!' : 'Product ingredients fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching product ingredients!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**** Stocks ****/

    public function saveStock(Request $request)
    {
        $request->validate([
            '*.ingredient_name' => 'required|string',
            '*.stock_unit' => 'required|numeric',
            '*.quantity_received' => 'required|numeric',
            '*.stock_alert_qty' => 'required|numeric',
            '*.stock_unit_cost' => 'required|numeric',
            '*.branch_id' => 'required|numeric',
        ]);
        try {
            foreach ($request->all() as $item) {
                $shopId = $request->user()->shop_id;
                $branchId = $request->user()->branch_id;
                $userId = $request->user()->admin_id;
                $stock = new StocksModel();
                $stock->ingredient_name = $item['ingredient_name'];
                $stock->stock_unit = $item['stock_unit'];
                $stock->quantity_received = $item['quantity_received'];
                $stock->stock_alert_qty = $item['stock_alert_qty'];
                $stock->stock_unit_cost = $item['stock_unit_cost'];
                $stock->availability_id = 1;
                $stock->shop_id = $shopId;
                $stock->branch_id = $item['branch_id'];
                $stock->user_id = $userId;
                $stock->created_at = now();
                $stock->updated_at = now();
                $stock->save();
                $newStockId = $stock->ingredient_id;
                $shopId = $stock->shop_id;
                $branchId = $stock->branch_id;
                $userId = $stock->user_id;
                StocksHistoryModel::create([
                    'ingredient_id' => $newStockId,
                    'manage_id' => 1, // SAVE
                    'description' => 'New Stock Saved',
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
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

    public function updateStock(Request $request, $ingredient_id)
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|integer',
            'ingredient_name' => 'required|string',
            'quantity_received' => 'required|numeric',
            'stock_unit' => 'required|integer',
            'stock_out' => 'nullable|integer',
            'stock_alert_qty' => 'required|integer',
            'availability_id' => 'required|integer',
            'stock_unit_cost' => 'required|numeric',
            'shop_id' => 'required|integer',
            'branch_id' => 'required|integer',
        ]);
        try {

            if (isset($validated['quantity_received']) && $validated['quantity_received'] === 1 || $validated['quantity_received'] === 0) {
                $validated['availability_id'] = 2;
            }
            if (isset($validated['quantity_received']) && $validated['quantity_received'] > 1) {
                $validated['availability_id'] = 1;
            }
            try {
                $stock = StocksModel::findOrFail($ingredient_id);
                $originalValues = $stock->getOriginal();
                $availabilityChanged = isset($validated['availability_id']) &&
                    $validated['availability_id'] != $originalValues['availability_id'];
                $isBecomingAvailable = $availabilityChanged && $validated['availability_id'] == 1;
                $isBecomingUnavailable = $availabilityChanged && $validated['availability_id'] == 2;
                $stock->update($validated);
                $newStockId = $stock->ingredient_id;
                $shopId = $stock->shop_id;
                $branchId = $stock->branch_id;
                $userId = auth()->user()->admin_id;
                $changes = [];
                foreach ($validated as $key => $value) {
                    if ($originalValues[$key] != $value) {
                        $changes[$key] = [
                            'from' => $originalValues[$key],
                            'to' => $value
                        ];
                    }
                }
                $relatedProducts = ProductIngredientsModel::where('ingredient_id', $ingredient_id)
                    ->with('product')
                    ->get()
                    ->pluck('product')
                    ->unique();
                foreach ($relatedProducts as $product) {
                    $originalProductAvailability = $product->availability_id;
                    if ($isBecomingUnavailable) {
                        if ($product->availability_id != 2) {
                            $product->update(['availability_id' => 2]);
                            ProductsHistoryModel::create([
                                'product_id' => $product->product_id,
                                'manage_id' => 2, // UPDATE
                                'shop_id' => $product->shop_id,
                                'branch_id' => $product->branch_id,
                                'user_id' => $userId,
                                'description' => 'Automatically set to Not Available because one of its ingredients became unavailable',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } elseif ($isBecomingAvailable) {
                        $allIngredientsAvailable = true;
                        foreach ($product->ingredients as $ingredient) {
                            if ($ingredient->stock->availability_id != 1) {
                                $allIngredientsAvailable = false;
                                break;
                            }
                        }
                        if ($allIngredientsAvailable && $originalProductAvailability == 2) {
                            $product->update(['availability_id' => 1]);
                            ProductsHistoryModel::create([
                                'product_id' => $product->product_id,
                                'manage_id' => 2, // UPDATE
                                'shop_id' => $product->shop_id,
                                'branch_id' => $product->branch_id,
                                'user_id' => $userId,
                                'description' => 'Automatically set to Available because all ingredients are now available',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
                $description = '';
                foreach ($changes as $field => $change) {
                    if ($field === 'availability_id') {
                        $fromAvailability = AvailabilityModel::find($change['from']);
                        $toAvailability = AvailabilityModel::find($change['to']);
                        $fromLabel = $fromAvailability ? $fromAvailability->availability_label : $change['from'];
                        $toLabel = $toAvailability ? $toAvailability->availability_label : $change['to'];
                        $description .= "Availability: From [{$fromLabel}] To [{$toLabel}]. ";
                    } elseif ($field === 'stock_unit') {
                        $fromUnit = UnitModel::find($change['from']);
                        $toUnit = UnitModel::find($change['to']);
                        $fromLabel = $fromUnit ? $fromUnit->unit_label : $change['from'];
                        $toLabel = $toUnit ? $toUnit->unit_label : $change['to'];
                        $description .= "Unit: From [{$fromLabel}] To [{$toLabel}]. ";
                    } else {
                        $description .= ucfirst(str_replace('_', ' ', $field)) . ": From [{$change['from']}] To [{$change['to']}]. ";
                    }
                }
                if (empty($description)) {
                    $description = 'No fields were updated';
                }
                StocksHistoryModel::create([
                    'ingredient_id' => $newStockId,
                    'manage_id' => 2, // UPDATE
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'description' => trim($description),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return response()->json([
                    'status' => true,
                    'message' => 'Stock updated successfully',
                    'updated_at' => now('Asia/Manila')->toDateTimeString(),
                    'changes' => $changes
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error updating stocks!',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATED
    public function getStocks($branchId)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $data = IngredientsModel::select(
                'tbl_ingredients.ingredient_id',
                'tbl_ingredients.ingredient_name',
                'tbl_ingredients.base_unit_id',
                'tbl_ingredients.alert_quantity',
                'tbl_ingredients.availability_id',
                'tbl_ingredients.shop_id',
                'tbl_ingredients.branch_id',
                'tbl_ingredients.updated_at',
                'tbl_stock_batches.quantity_remaining',
                'tbl_stock_batches.unit_cost',
                'tbl_ingredient_unit.unit_label',
                'tbl_ingredient_unit.unit_avb',
                'tbl_availability.availability_label'
            )
                ->join('tbl_stock_batches', 'tbl_ingredients.ingredient_id', '=', 'tbl_stock_batches.ingredient_id')
                ->join('tbl_ingredient_unit', 'tbl_ingredients.base_unit_id', '=', 'tbl_ingredient_unit.ingredient_unit_id')
                ->join('tbl_availability', 'tbl_ingredients.availability_id', '=', 'tbl_availability.availability_id')
                ->where('tbl_ingredients.shop_id', $shopId)
                ->where('tbl_ingredients.branch_id', $branchId)
                ->orderByDesc('tbl_ingredients.updated_at')
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No stocks found!' : 'Stocks fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stocks!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStocksList($branchId)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $data = StocksModel::select(
                'tbl_stocks.ingredient_id',
                'tbl_stocks.ingredient_name',
            )
                ->where('tbl_stocks.shop_id', $shopId)
                ->where('tbl_stocks.branch_id', $branchId)
                ->orderBy('tbl_stocks.ingredient_name')
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No stocks name found!' : 'Stocks name fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stocks name!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStocksNameBasedId($branchId, $stockId)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $productIds = ProductIngredientsModel::where('ingredient_id', $stockId)
                ->pluck('product_id')
                ->toArray();
            $excludedStockIds = ProductIngredientsModel::whereIn('product_id', $productIds)
                ->pluck('ingredient_id')
                ->unique()
                ->toArray();
            if (!in_array($stockId, $excludedStockIds)) {
                $excludedStockIds[] = $stockId;
            }
            Log::debug("Excluded stock IDs: " . implode(',', $excludedStockIds));
            $query = StocksModel::select(
                'tbl_stocks.ingredient_id',
                'tbl_stocks.ingredient_name',
                'tbl_stocks.availability_id'
            )
                ->where('tbl_stocks.shop_id', $shopId)
                ->where('tbl_stocks.branch_id', $branchId)
                ->where('tbl_stocks.availability_id', 1)
                ->whereNotIn('tbl_stocks.ingredient_id', $excludedStockIds);
            Log::debug("Final SQL: " . $query->toSql());
            Log::debug("Bindings: " . json_encode($query->getBindings()));
            $data = $query->orderBy('tbl_stocks.ingredient_name')
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No available stocks found!' : 'Stocks fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stocks!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getStocksReport($branchId, Request $request)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $dateType = $request->query('date_filter');

            $stocksQuery = IngredientsModel::select(
                'tbl_ingredients.ingredient_id',
                'tbl_ingredients.ingredient_name',
                'tbl_ingredients.base_unit_id',
                'tbl_stock_batches.quantity_received',
                'tbl_ingredients.updated_at',
                DB::raw('AVG(tbl_product_items.ingredient_capital)')
            )
                ->join('tbl_stock_batches', 'tbl_ingredients.ingredient_id', '=', 'tbl_stock_batches.ingredient_id')
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
                    'tbl_stock_batches.quantity_received',
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
                        DB::raw('SUM(tbl_order_items.quantity * tbl_product_items.unit_usage) as total_usage'),
                        DB::raw('SUM(tbl_order_items.quantity * tbl_product_items.ingredient_capital) as total_amount'),
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
                $stock->total_amount = $stockOutResult->total_amount ?? 0;
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
    public function getStockNotifQty()
    {
        try {

            $shopId = auth()->user()->shop_id;

            $lowStocks = StockBatchesModel::join('tbl_ingredients', 'tbl_ingredients.ingredient_id', '=', 'tbl_stock_batches.ingredient_id')
                ->where('tbl_stock_batches.shop_id', $shopId)
                ->whereColumn('tbl_stock_batches.quantity_remaining', '<=', 'tbl_ingredients.alert_quantity')
                ->selectRaw('tbl_stock_batches.branch_id, COUNT(*) as total')
                ->groupBy('tbl_stock_batches.branch_id')
                ->with('branches')
                ->get();

            $branchesWithLowStock = [];
            $totalLowStock = 0;

            foreach ($lowStocks as $item) {

                $branch = $item->branches;

                $branchesWithLowStock[$item->branch_id] = [
                    'name' => $branch->branch_name ?? 'Unknown Branch',
                    'count' => $item->total
                ];

                $totalLowStock += $item->total;
            }

            return response()->json([
                'status' => true,
                'message' => 'Low stock count fetched successfully!',
                'data' => [
                    'total_count' => $totalLowStock,
                    'branches' => $branchesWithLowStock
                ]
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Error getting low stocks!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getStocksHistory($branchId)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $data = StocksHistoryModel::select(
                'tbl_stocks.ingredient_name',
                'tbl_stocks_history.manage_id',
                'tbl_stocks_history.description',
                'tbl_admin.admin_name',
                'tbl_stocks_history.updated_at',
            )
                ->join('tbl_stocks', 'tbl_stocks_history.ingredient_id', '=', 'tbl_stocks.ingredient_id')
                ->join('tbl_admin', 'tbl_stocks_history.user_id', '=', 'tbl_admin.admin_id')
                ->where('tbl_stocks_history.shop_id', $shopId)
                ->where('tbl_stocks_history.branch_id', $branchId)
                ->orderBy('tbl_stocks_history.updated_at')
                ->get();
            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No stocks found!' : 'Stocks history fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stocks!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getStocksOnly($branchId)
    {
        try {
            $shopId = auth()->user()->shop_id;
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

    /**** Orders ****/

    // UPDATED
    public function getOrdersByDateType($branchId, Request $request)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $dateType = $request->query('date_filter');

            $query = OrdersModel::select(
                'tbl_orders.reference_number',
                'tbl_orders.total_quantity',
                'tbl_orders.customer_cash',
                'tbl_orders.customer_change',
                'tbl_orders.updated_at',
                'tbl_sales.total_amount',
                'tbl_sales.discount_amount',
                'tbl_payment_method.payment_method_name',
                'tbl_cashier.cashier_name',
            )
                ->join('tbl_sales', 'tbl_orders.order_id', '=', 'tbl_sales.order_id')
                ->join('tbl_cashier', 'tbl_orders.user_id', '=', 'tbl_cashier.cashier_id')
                ->join('tbl_payment_method', 'tbl_sales.payment_method_id', '=', 'tbl_payment_method.payment_method_id')
                ->where('tbl_orders.shop_id', $shopId)
                ->where('tbl_orders.branch_id', $branchId)
                ->where('tbl_orders.order_status_id', 3);

            if ($dateType) {
                switch ($dateType) {
                    case 1: // Today
                        $query->whereDate('tbl_orders.updated_at', now());
                        break;
                    case 2: // Yesterday
                        $query->whereDate('tbl_orders.updated_at', now()->subDay());
                        break;
                    case 3: // Last 7 days
                        $query->whereDate('tbl_orders.updated_at', '>=', now()->subDays(7));
                        break;
                    case 4: // This week
                        $query->whereDate('tbl_orders.updated_at', '>=', now()->startOfWeek());
                        break;
                    case 5: // Last 30 days
                        $query->whereDate('tbl_orders.updated_at', '>=', now()->subDays(30));
                        break;
                    case 6: // This month
                        $query->whereMonth('tbl_orders.updated_at', now()->month);
                        break;
                    case 7: // Last month
                        $query->whereMonth('tbl_orders.updated_at', now()->subMonth()->month);
                        break;
                }
            }

            $data = $query->orderBy('tbl_orders.created_at', 'desc')->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No orders found!' : 'Orders fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching orders', [
                'shop_id' => auth()->user()->shop_id,
                'branch_id' => $branchId,
                'date_filter' => $dateType,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Error fetching orders!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATED
    public function getOrdersOnly($branchId, Request $request)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $dateType = $request->query('date_filter');
            $query = OrdersModel::select(
                DB::raw('COUNT(tbl_orders.reference_number) as total_orders'),
                DB::raw('MAX(tbl_orders.updated_at) as updated_at'),
            )
                ->where('tbl_orders.shop_id', $shopId)
                ->where('tbl_orders.branch_id', $branchId)
                ->where('tbl_orders.order_status_id', 3);
            if ($dateType) {
                $query->whereMonth('tbl_orders.updated_at', $dateType)
                    ->whereYear('tbl_orders.updated_at', date('Y'));
            }
            $totalOrders = $query->first();
            return response()->json([
                'status' => true,
                'message' => 'Total orders fetched successfully!',
                'data' => [
                    'total_orders' => $totalOrders->total_orders ?? 0
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

    /**** Sales ****/

    // UPDATED
    public function getSalesByDateType($branchId, Request $request)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $dateType = $request->query('date_filter');
            $query = OrderItemsModel::select(
                'tbl_order_items.product_id',
                DB::raw('SUM(tbl_order_items.quantity) as total_quantity'),
                DB::raw('SUM(tbl_order_items.quantity * tbl_products.base_price) as gross_sales'),
                DB::raw('MAX(tbl_order_items.updated_at) as updated_at'),
                'tbl_orders.order_id',
                'tbl_products.product_name',
                'tbl_products.base_price',
                'tbl_products.category_id',
                'tbl_products.shop_id as shop_id',
                'tbl_products.branch_id as branch_id',
                'tbl_product_category.category_label',
                'tbl_product_temp.temp_label',
                'tbl_product_size.size_label',
            )
                ->join('tbl_orders', 'tbl_order_items.order_id', '=', 'tbl_orders.order_id')
                ->join('tbl_sales', 'tbl_order_items.order_id', '=', 'tbl_sales.order_id')
                ->join('tbl_products', 'tbl_order_items.product_id', '=', 'tbl_products.product_id')
                ->join('tbl_product_temp', 'tbl_products.temp_id', '=', 'tbl_product_temp.product_temp_id')
                ->join('tbl_product_size', 'tbl_products.size_id', '=', 'tbl_product_size.product_size_id')
                ->join('tbl_product_category', 'tbl_products.category_id', '=', 'tbl_product_category.product_category_id')
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.branch_id', $branchId)
                ->where('tbl_orders.order_status_id', 3)
                ->groupBy(
                    'tbl_order_items.product_id',
                    'tbl_orders.order_id',
                    'tbl_products.product_name',
                    'tbl_products.base_price',
                    'tbl_products.category_id',
                    'tbl_products.shop_id',
                    'tbl_products.branch_id',
                    'tbl_product_category.category_label',
                    'tbl_product_temp.temp_label',
                    'tbl_product_size.size_label',
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
                DB::raw('SUM(tbl_order_items.quantity * tbl_products.base_price * (1 - tbl_sales.discount_amount/100)) as discounted_sales')
            )
                ->join('tbl_orders', 'tbl_order_items.order_id', '=', 'tbl_orders.order_id')
                ->join('tbl_sales', 'tbl_order_items.order_id', '=', 'tbl_sales.order_id')
                ->join('tbl_products', 'tbl_order_items.product_id', '=', 'tbl_products.product_id')
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.branch_id', $branchId)
                ->where('tbl_orders.order_status_id', 3);

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
            $shopId = auth()->user()->shop_id;
            $year = $request->query('year', date('Y'));
            $dateType = $request->query('date_filter');
            $day = $request->query('day', date('d'));
            $query = SalesModel::select(
                DB::raw('SUM(tbl_sales.total_amount) as total_sales'),
                DB::raw('MAX(tbl_sales.updated_at) as updated_at'),
            )
                ->where('tbl_sales.shop_id', $shopId)
                ->where('tbl_sales.branch_id', $branchId)
                ->where('tbl_sales.sale_status', 'PAID');
            if ($dateType) {
                $query->whereYear('tbl_sales.updated_at', $year)
                    ->whereMonth('tbl_sales.updated_at', $dateType);
            }
            $data = $query->groupBy(DB::raw('DATE(tbl_sales.updated_at)'))
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
            $shopId = auth()->user()->shop_id;
            $dateType = $request->query('date_filter');
            $query = SalesModel::select(
                DB::raw('SUM(tbl_sales.total_amount) as total_sales'),
                DB::raw('MAX(tbl_sales.updated_at) as updated_at'),
            )
                ->where('tbl_sales.shop_id', $shopId)
                ->where('tbl_sales.branch_id', $branchId)
                ->where('tbl_sales.sale_status', 'PAID');
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

    /**** Void ****/

    // UPDATED
    public function getVoidOrders($branchId, Request $request)
    {
        try {
            $shopId = auth()->user()->shop_id;
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
            $shopId = auth()->user()->shop_id;
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

    public function getEwalletImage($folderName, $imageFileName)
    {
        $folderName = $this->$folderName;
        $imageFileName = $this->$imageFileName;
        $folderPath = storage_path('app/e-Wallet_Evidence/' . $folderName . '/' . $imageFileName);
        if (!File::exists($folderPath)) {
            abort(404, 'Image not found');
        }
        return response()->file($folderPath, [
            'Content-Type' => File::mimeType($folderPath),
            'Content-Disposition' => 'inline'
        ]);
    }

    /**** Options ****/

    // UPDATED
    public function getStockUnits()
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
            $data = CategoryModel::all();
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
