<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\BranchModel;
use App\Models\IngredientsModel;
use App\Models\ProductsModel;
use App\Models\ProductsHistoryModel;
use App\Models\StocksHistoryModel;
use App\Models\StocksModel;
use App\Models\TemperatureModel;
use App\Models\SizeModel;
use App\Models\CategoryModel;
use App\Models\AvailabilityModel;
use App\Models\UnitModel;
use App\Models\StationModel;
use App\Models\TransactionModel;
use App\Models\TransactionOrdersModel;
use App\Models\TransactionVoidModel;
use App\Models\VoidStatusModel;

// For Dashboard
// For Analytics
/*  BRANCH SECTION  */
/*  PRODUCT SECTION  */
/*  INGREDIENT SECTION  */
/*  STOCK SECTION  */
/*  ORDER SECTION  */
/*  SALES SECTION  */
/*  VOID SECTION  */
/*  OPTIONS AND PRE-DEFINED ITEMS  */

class AdminController extends Controller
{
    /*  BRANCH SECTION  */

    public function saveBranch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_name' => 'required|string',
            'branch_location' => 'required|string',
            'm_name' => 'required|string',
            'contact' => 'required|string',
            'm_email' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        try {
            $shopId = $request->user()->shop_id;
            DB::transaction(function () use ($request, $shopId) {
                // Create the new branch
                $newBranch = BranchModel::create([
                    'shop_id' => $shopId,
                    'branch_name' => $request->input('branch_name'),
                    'branch_location' => $request->input('branch_location'),
                    'm_name' => $request->input('m_name'),
                    'm_email' => $request->input('m_email'),
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

                // 1. Copy stocks
                $stocks = StocksModel::where('branch_id', $newBranchId)->get();
                $stockMap = [];

                foreach ($stocks as $stock) {
                    $newStock = $stock->replicate();
                    $newStock->branch_id = $newBranch->branch_id;
                    $newStock->user_id = $userId;
                    $newStock->created_at = now();
                    $newStock->updated_at = now();
                    $newStock->save();
                    $stockMap[$stock->stock_id] = $newStock->stock_id;
                }

                // 2. Copy products
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

                // 3. Copy ingredients
                $ingredients = IngredientsModel::where('branch_id', $newBranchId)->get();
                foreach ($ingredients as $ingredient) {
                    $newIngredient = $ingredient->replicate();
                    $newIngredient->branch_id = $newBranch->branch_id;
                    $newIngredient->product_id = $productMap[$ingredient->product_id] ?? null;
                    $newIngredient->stock_id = $stockMap[$ingredient->stock_id] ?? null;
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

    public function getShopBranches()
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $branches = BranchModel::where('shop_id', $shopId)
                ->where('status_id', 1)
                ->pluck('branch_name');
            return response()->json([
                'success' => true,
                'branches' => $branches
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getBranchDetails($branchName)
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $branch = BranchModel::select(
                'tbl_shop.shop_name',
                'tbl_shop.shop_logo_link',
                'tbl_shop_branch.shop_id',
                'tbl_shop_branch.branch_id',
                'tbl_shop_branch.branch_name',
                'tbl_shop_branch.branch_location',
                'tbl_shop_branch.m_name',
                'tbl_shop_branch.m_email',
                'tbl_shop_branch.contact',
                'tbl_admin.admin_name'
            )
                ->join('tbl_shop', 'tbl_shop_branch.shop_id', '=', 'tbl_shop.shop_id')
                ->join('tbl_admin', 'tbl_shop.shop_id', '=', 'tbl_admin.shop_id')
                ->where('tbl_shop_branch.branch_name', urldecode($branchName))
                ->where('tbl_shop_branch.shop_id', $shopId)
                ->first();
            if ($branch && $branch->branch_location) {
                $branch->branch_location = str_replace(',', '', $branch->branch_location);
            }
            if (!$branch) {
                return response()->json(['error' => 'Branch not found'], 404);
            }
            return response()->json($branch);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /*  PRODUCT SECTION  */
  
    public function saveProduct(Request $request)
    {
        $request->validate([
            '*.product_name' => 'required|string',
            '*.product_price' => 'required|numeric',
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
                $product->product_price = $item['product_price'];
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

    public function updateProduct(Request $request, $product_id)
    {
        $validated = $request->validate([
            'availability_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'product_category_id' => 'required|integer',
            'product_id' => 'required|integer',
            'product_name' => 'required|string',
            'product_price' => 'required|numeric',
            'product_size_id' => 'required|integer',
            'product_temp_id' => 'required|integer',
            'shop_id' => 'required|integer',
        ]);

        try {
            $userId = Auth::guard('api')->user()->admin_id;
            $product = ProductsModel::findOrFail($product_id);
            $originalValues = $product->getOriginal();
            if ($validated['availability_id'] == 1 && $originalValues['availability_id'] != 1) {
                $ingredientStockIds = IngredientsModel::where('product_id', $product_id)
                    ->pluck('stock_id')
                    ->toArray();
                if (!empty($ingredientStockIds)) {
                    $unavailableStocks = StocksModel::whereIn('stock_id', $ingredientStockIds)
                        ->where('availability_id', '!=', 1)
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
                if ($field === 'product_price') {
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

    public function getProducts($branchId)
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $data = ProductsModel::select(
                'tbl_products.product_id',
                'tbl_products.product_name',
                'tbl_products.product_price',
                'tbl_products.product_temp_id',
                'tbl_products.product_size_id',
                'tbl_products.updated_at',
                'tbl_products.product_category_id',
                'tbl_products.availability_id',
                'tbl_temp.temp_label',
                'tbl_size.size_label',
                'tbl_category.category_label',
                'tbl_availability.availability_label',
                'tbl_products.branch_id',
                'tbl_products.shop_id',
            )
                ->join('tbl_temp', 'tbl_products.product_temp_id', '=', 'tbl_temp.temp_id')
                ->join('tbl_size', 'tbl_products.product_size_id', '=', 'tbl_size.size_id')
                ->join('tbl_category', 'tbl_products.product_category_id', '=', 'tbl_category.category_id')
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
            $shopId = Auth::guard('api')->user()->shop_id;
            $branchId = Auth::guard('api')->user()->branch_id;
            $data = ProductsModel::select(
                'tbl_products.product_id',
                'tbl_products.product_name',
                'tbl_products.product_price',
                'tbl_products.product_temp_id',
                'tbl_products.product_size_id',
                'tbl_products.updated_at',
                'tbl_products.product_category_id',
                'tbl_products.availability_id',
                'tbl_temp.temp_label',
                'tbl_size.size_label',
                'tbl_category.category_label',
                'tbl_availability.availability_label',
                'tbl_products.branch_id',
                'tbl_products.shop_id',
            )
                ->join('tbl_temp', 'tbl_products.product_temp_id', '=', 'tbl_temp.temp_id')
                ->join('tbl_size', 'tbl_products.product_size_id', '=', 'tbl_size.size_id')
                ->join('tbl_category', 'tbl_products.product_category_id', '=', 'tbl_category.category_id')
                ->join('tbl_availability', 'tbl_products.availability_id', '=', 'tbl_availability.availability_id')
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.branch_id', $branchId)
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
            $shopId = Auth::guard('api')->user()->shop_id;
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
            $shopId = Auth::guard('api')->user()->shop_id;
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

    /*  INGREDIENT SECTION  */

    public function saveProductIngredients(Request $request)
    {
        $shopId = Auth::guard('api')->user()->shop_id;
        $request->validate([
            '*.shop_id' => 'required|integer',
            '*.branch_id' => 'required|integer',
            '*.product_id' => 'required|integer',
            '*.stock_id' => 'required|integer',
            '*.unit_usage' => 'required|numeric',
            '*.ingredient_capital' => 'required|numeric',
        ]);
        try {
            foreach ($request->all() as $item) {
                IngredientsModel::create([
                    'product_id' => $item['product_id'],
                    'unit_usage' => $item['unit_usage'],
                    'ingredient_capital' => $item['ingredient_capital'],
                    'stock_id' => $item['stock_id'],
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
            'stock_id' => 'required|integer',
            'unit_usage' => 'required|numeric',
            'ingredient_capital' => 'required|numeric',
        ]);
        try {
            $userId = Auth::guard('api')->user()->admin_id;
            $ingredient = IngredientsModel::findOrFail($ingredient_id);
            $originalValues = $ingredient->getOriginal();
            $ingredient->update($validated);
            $ingredient->load(['product', 'stock']);
            $newProductId = $ingredient->product_id;
            $shopId = $ingredient->shop_id;
            $branchId = $ingredient->branch_id;
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
                if ($field === 'stock_id') {
                    $from = StocksModel::find($change['from']);
                    $to = StocksModel::find($change['to']);
                    $fromLabel = $from ? $from->stock_ingredient : $change['from'];
                    $toLabel = $to ? $to->stock_ingredient : $change['to'];
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
            $shopId = Auth::guard('api')->user()->shop_id;
            $branchId = Auth::guard('api')->user()->branch_id;
            $data = IngredientsModel::select(
                'tbl_product_ingredient.product_ingredient_id',
                'tbl_product_ingredient.product_id',
                'tbl_product_ingredient.unit_usage',
                'tbl_product_ingredient.ingredient_capital',
                'tbl_product_ingredient.updated_at',
                'tbl_products.product_name',
                'tbl_temp.temp_label',
                'tbl_size.size_label',
                'tbl_stocks.stock_id',
                'tbl_stocks.branch_id',
                'tbl_stocks.stock_ingredient',
                'tbl_availability.availability_label',
                'tbl_unit.unit_avb',
            )
                ->join('tbl_products', 'tbl_product_ingredient.product_id', '=', 'tbl_products.product_id')
                ->join('tbl_temp', 'tbl_products.product_temp_id', '=', 'tbl_temp.temp_id')
                ->join('tbl_size', 'tbl_products.product_size_id', '=', 'tbl_size.size_id')
                ->join('tbl_stocks', 'tbl_product_ingredient.stock_id', '=', 'tbl_stocks.stock_id')
                ->join('tbl_availability', 'tbl_stocks.availability_id', '=', 'tbl_availability.availability_id')
                ->join('tbl_unit', 'tbl_stocks.stock_unit', '=', 'tbl_unit.unit_id')
                ->where('tbl_product_ingredient.shop_id', $shopId)
                ->where('tbl_product_ingredient.branch_id', $branchId)
                ->where('tbl_product_ingredient.product_id', $product_id)
                ->orderBy('tbl_stocks.stock_ingredient')
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

    /*  STOCK SECTION  */

    public function saveStock(Request $request)
    {
        $request->validate([
            '*.stock_ingredient' => 'required|string',
            '*.stock_unit' => 'required|numeric',
            '*.stock_in' => 'required|numeric',
            '*.stock_alert_qty' => 'required|numeric',
            '*.stock_cost_per_unit' => 'required|numeric',
            '*.branch_id' => 'required|numeric',
        ]);
        try {
            foreach ($request->all() as $item) {
                $shopId = $request->user()->shop_id;
                $branchId = $request->user()->branch_id;
                $userId = $request->user()->admin_id;
                $stock = new StocksModel();
                $stock->stock_ingredient = $item['stock_ingredient'];
                $stock->stock_unit = $item['stock_unit'];
                $stock->stock_in = $item['stock_in'];
                $stock->stock_alert_qty = $item['stock_alert_qty'];
                $stock->stock_cost_per_unit = $item['stock_cost_per_unit'];
                $stock->availability_id = 1;
                $stock->shop_id = $shopId;
                $stock->branch_id = $item['branch_id'];
                $stock->user_id = $userId;
                $stock->created_at = now();
                $stock->updated_at = now();
                $stock->save();
                $newStockId = $stock->stock_id;
                $shopId = $stock->shop_id;
                $branchId = $stock->branch_id;
                $userId = $stock->user_id;
                StocksHistoryModel::create([
                    'stock_id' => $newStockId,
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

    public function updateStock(Request $request, $stock_id)
    {
        try {
            $userId = Auth::guard('api')->user()->admin_id;
            $validated = $request->validate([
                'stock_id' => 'required|integer',
                'stock_ingredient' => 'required|string',
                'stock_in' => 'required|numeric',
                'stock_unit' => 'required|integer',
                'stock_out' => 'nullable|integer',
                'availability_id' => 'required|integer',
                'stock_cost_per_unit' => 'required|numeric',
                'shop_id' => 'required|integer',
                'branch_id' => 'required|integer',
            ]);
            if (isset($validated['stock_in']) && $validated['stock_in'] === 1 || $validated['stock_in'] === 0) {
                $validated['availability_id'] = 2;
            }
            if (isset($validated['stock_in']) && $validated['stock_in'] > 1) {
                $validated['availability_id'] = 1;
            }
            try {
                $stock = StocksModel::findOrFail($stock_id);
                $originalValues = $stock->getOriginal();
                $availabilityChanged = isset($validated['availability_id']) &&
                    $validated['availability_id'] != $originalValues['availability_id'];
                $isBecomingAvailable = $availabilityChanged && $validated['availability_id'] == 1;
                $isBecomingUnavailable = $availabilityChanged && $validated['availability_id'] == 2;
                $stock->update($validated);
                $newStockId = $stock->stock_id;
                $shopId = $stock->shop_id;
                $branchId = $stock->branch_id;
                $changes = [];
                foreach ($validated as $key => $value) {
                    if ($originalValues[$key] != $value) {
                        $changes[$key] = [
                            'from' => $originalValues[$key],
                            'to' => $value
                        ];
                    }
                }
                $relatedProducts = IngredientsModel::where('stock_id', $stock_id)
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
                    'stock_id' => $newStockId,
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

    public function getStocks($branchId)
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $data = StocksModel::select(
                'tbl_stocks.stock_id',
                'tbl_stocks.stock_ingredient',
                'tbl_stocks.stock_unit',
                'tbl_stocks.stock_in',
                'tbl_stocks.stock_alert_qty',
                'tbl_stocks.stock_cost_per_unit',
                'tbl_stocks.availability_id',
                'tbl_stocks.shop_id',
                'tbl_stocks.branch_id',
                'tbl_stocks.updated_at',
                'tbl_unit.unit_label',
                'tbl_unit.unit_avb',
                'tbl_availability.availability_label'
            )
                ->join('tbl_unit', 'tbl_stocks.stock_unit', '=', 'tbl_unit.unit_id')
                ->join('tbl_availability', 'tbl_stocks.availability_id', '=', 'tbl_availability.availability_id')
                ->where('tbl_stocks.shop_id', $shopId)
                ->where('tbl_stocks.branch_id', $branchId)
                ->orderByDesc('tbl_stocks.updated_at')
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
            $shopId = Auth::guard('api')->user()->shop_id;
            $data = StocksModel::select(
                'tbl_stocks.stock_id',
                'tbl_stocks.stock_ingredient',
            )
                ->where('tbl_stocks.shop_id', $shopId)
                ->where('tbl_stocks.branch_id', $branchId)
                ->orderBy('tbl_stocks.stock_ingredient')
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
            $shopId = Auth::guard('api')->user()->shop_id;
            $productIds = IngredientsModel::where('stock_id', $stockId)
                ->pluck('product_id')
                ->toArray();
            $excludedStockIds = IngredientsModel::whereIn('product_id', $productIds)
                ->pluck('stock_id')
                ->unique()
                ->toArray();
            if (!in_array($stockId, $excludedStockIds)) {
                $excludedStockIds[] = $stockId;
            }
            Log::debug("Excluded stock IDs: " . implode(',', $excludedStockIds));
            $query = StocksModel::select(
                'tbl_stocks.stock_id',
                'tbl_stocks.stock_ingredient',
                'tbl_stocks.availability_id'
            )
                ->where('tbl_stocks.shop_id', $shopId)
                ->where('tbl_stocks.branch_id', $branchId)
                ->where('tbl_stocks.availability_id', 1)
                ->whereNotIn('tbl_stocks.stock_id', $excludedStockIds);
            Log::debug("Final SQL: " . $query->toSql());
            Log::debug("Bindings: " . json_encode($query->getBindings()));
            $data = $query->orderBy('tbl_stocks.stock_ingredient')
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

    public function getStocksReport($branchId, Request $request)
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $dateType = $request->query('date_filter');

            // First, get only stocks that exist in transaction orders
            $stocksQuery = StocksModel::select(
                'tbl_stocks.stock_id',
                'tbl_stocks.stock_ingredient',
                'tbl_stocks.stock_unit',
                'tbl_stocks.stock_in',
                'tbl_stocks.updated_at',
                DB::raw('AVG(tbl_product_ingredient.ingredient_capital) as ingredient_capital')
            )
                ->join('tbl_product_ingredient', 'tbl_stocks.stock_id', '=', 'tbl_product_ingredient.stock_id')
                ->join('tbl_transaction_orders', 'tbl_product_ingredient.product_id', '=', 'tbl_transaction_orders.product_id')
                ->join('tbl_transaction', 'tbl_transaction_orders.transaction_id', '=', 'tbl_transaction.transaction_id')
                ->where('tbl_stocks.shop_id', $shopId)
                ->where('tbl_stocks.branch_id', $branchId)
                ->where('tbl_transaction.order_status_id', 3) // Completed orders only
                ->groupBy(
                    'tbl_stocks.stock_id',
                    'tbl_stocks.stock_ingredient',
                    'tbl_stocks.stock_unit',
                    'tbl_stocks.stock_in',
                    'tbl_stocks.updated_at',
                );

            // Apply date filter if provided
            if ($dateType) {
                switch ($dateType) {
                    case 1: // Today
                        $stocksQuery->whereDate('tbl_transaction_orders.updated_at', now());
                        break;
                    case 2: // Yesterday
                        $stocksQuery->whereDate('tbl_transaction_orders.updated_at', now()->subDay());
                        break;
                    case 3: // Last 7 days
                        $stocksQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->subDays(7));
                        break;
                    case 4: // This week
                        $stocksQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->startOfWeek());
                        break;
                    case 5: // Last 30 days
                        $stocksQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->subDays(30));
                        break;
                    case 6: // This month
                        $stocksQuery->whereMonth('tbl_transaction_orders.updated_at', now()->month);
                        break;
                    case 7: // Last month
                        $stocksQuery->whereMonth('tbl_transaction_orders.updated_at', now()->subMonth()->month);
                        break;
                }
            }

            $stocks = $stocksQuery->get();

            // Calculate stock_out for each stock
            foreach ($stocks as $stock) {
                $stockOutQuery = DB::table('tbl_transaction_orders')
                    ->join('tbl_product_ingredient', 'tbl_transaction_orders.product_id', '=', 'tbl_product_ingredient.product_id')
                    ->join('tbl_transaction', 'tbl_transaction_orders.transaction_id', '=', 'tbl_transaction.transaction_id')
                    ->where('tbl_product_ingredient.stock_id', $stock->stock_id)
                    ->where('tbl_transaction.order_status_id', 3) // Completed orders only
                    ->select(
                        DB::raw('SUM(tbl_transaction_orders.quantity * tbl_product_ingredient.unit_usage) as total_usage'),
                        DB::raw('SUM(tbl_transaction_orders.quantity * tbl_product_ingredient.ingredient_capital) as total_amount'),
                        DB::raw('SUM(tbl_transaction_orders.quantity) as total_quantity')
                    );

                // Apply the same date filter to stock_out calculation
                if ($dateType) {
                    switch ($dateType) {
                        case 1:
                            $stockOutQuery->whereDate('tbl_transaction_orders.updated_at', now());
                            break;
                        case 2:
                            $stockOutQuery->whereDate('tbl_transaction_orders.updated_at', now()->subDay());
                            break;
                        case 3:
                            $stockOutQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->subDays(7));
                            break;
                        case 4:
                            $stockOutQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->startOfWeek());
                            break;
                        case 5:
                            $stockOutQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->subDays(30));
                            break;
                        case 6:
                            $stockOutQuery->whereMonth('tbl_transaction_orders.updated_at', now()->month);
                            break;
                        case 7:
                            $stockOutQuery->whereMonth('tbl_transaction_orders.updated_at', now()->subMonth()->month);
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
                'message' => $stocks->isEmpty() ? 'No stocks found in transactions!' : 'Stocks report fetched successfully!',
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

    public function getStockNotifQty()
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $lowStocks = StocksModel::with('branches')
                ->where('shop_id', $shopId)
                ->whereColumn('stock_in', '<=', 'stock_alert_qty')
                ->get()
                ->groupBy('branch_id');

            $branchesWithLowStock = [];
            $totalLowStock = 0;
            foreach ($lowStocks as $branchId => $items) {
                $branch = $items->first()->branches;
                $branchesWithLowStock[$branchId] = [
                    'name' => $branch->branch_name,
                    'count' => $items->count()
                ];
                $totalLowStock += $items->count();
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
                'message' => 'Error getching low stocks!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStocksHistory($branchId)
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $data = StocksHistoryModel::select(
                'tbl_stocks.stock_ingredient',
                'tbl_stocks_history.manage_id',
                'tbl_stocks_history.description',
                'tbl_admin.admin_name',
                'tbl_stocks_history.updated_at',
            )
                ->join('tbl_stocks', 'tbl_stocks_history.stock_id', '=', 'tbl_stocks.stock_id')
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
 
    public function getStocksOnly($branchId)
    {
        // For Dashboard
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $totalStocks = StocksModel::select(
                DB::raw('COUNT(tbl_stocks.stock_id) as total_stocks')
            )
                ->where('tbl_stocks.shop_id', $shopId)
                ->where('tbl_stocks.branch_id', $branchId)
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
   
    /*  ORDER SECTION  */

    public function getOrdersByDateType($branchId, Request $request)
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $dateType = $request->query('date_filter');

            $query = TransactionModel::select(
                'tbl_transaction.reference_number',
                'tbl_transaction.total_quantity',
                'tbl_transaction.customer_cash',
                'tbl_transaction.customer_charge',
                'tbl_transaction.customer_change',
                'tbl_transaction.updated_at',
                'tbl_cashier.cashier_name',
            )
                ->join('tbl_cashier', 'tbl_transaction.user_id', '=', 'tbl_cashier.cashier_id')
                ->where('tbl_transaction.shop_id', $shopId)
                ->where('tbl_transaction.branch_id', $branchId)
                ->where('tbl_transaction.order_status_id', 3);

            if ($dateType) {
                switch ($dateType) {
                    case 1: // Today
                        $query->whereDate('tbl_transaction.updated_at', now());
                        break;
                    case 2: // Yesterday
                        $query->whereDate('tbl_transaction.updated_at', now()->subDay());
                        break;
                    case 3: // Last 7 days
                        $query->whereDate('tbl_transaction.updated_at', '>=', now()->subDays(7));
                        break;
                    case 4: // This week
                        $query->whereDate('tbl_transaction.updated_at', '>=', now()->startOfWeek());
                        break;
                    case 5: // Last 30 days
                        $query->whereDate('tbl_transaction.updated_at', '>=', now()->subDays(30));
                        break;
                    case 6: // This month
                        $query->whereMonth('tbl_transaction.updated_at', now()->month);
                        break;
                    case 7: // Last month
                        $query->whereMonth('tbl_transaction.updated_at', now()->subMonth()->month);
                        break;
                }
            }

            $data = $query->orderBy('tbl_transaction.created_at', 'desc')->get();
            // Log for success
            Log::info('Orders fetched successfully', [
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'date_filter' => $dateType,
                'count' => $data->count()
            ]);

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No orders found!' : 'Orders fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            // Log for error
            Log::error('Error fetching orders', [
                'shop_id' => Auth::user()->shop_id,
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

    public function getOrdersOnly($branchId, Request $request)
    {
        // For Dashboard
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $dateType = $request->query('date_filter'); // added
            // $totalOrders to $query
            $query = TransactionModel::select(
                DB::raw('COUNT(tbl_transaction.reference_number) as total_orders'),
                DB::raw('MAX(tbl_transaction.updated_at) as updated_at'), // addedd
            )
                ->where('tbl_transaction.shop_id', $shopId)
                ->where('tbl_transaction.branch_id', $branchId)
                ->where('tbl_transaction.order_status_id', 3);
            // the content here is removed
            // added
            if ($dateType) {
                $query->whereMonth('tbl_transaction.updated_at', $dateType)
                    ->whereYear('tbl_transaction.updated_at', date('Y'));
            }
            $totalOrders = $query->first(); //added
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

    /*  SALES SECTION  */

    public function getSalesByDateType($branchId, Request $request)
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $dateType = $request->query('date_filter');
            $query = TransactionOrdersModel::select(
                'tbl_transaction_orders.product_id',
                DB::raw('SUM(tbl_transaction_orders.quantity) as total_quantity'),
                DB::raw('SUM(tbl_transaction_orders.quantity * tbl_products.product_price) as gross_sales'),
                DB::raw('MAX(tbl_transaction_orders.updated_at) as updated_at'),
                'tbl_products.product_name',
                'tbl_products.product_price',
                'tbl_products.product_category_id',
                'tbl_products.shop_id as shop_id',
                'tbl_products.branch_id as branch_id',
                'tbl_category.category_label',
                'tbl_temp.temp_label',
                'tbl_size.size_label',
            )
                ->join('tbl_transaction', 'tbl_transaction_orders.transaction_id', '=', 'tbl_transaction.transaction_id')
                ->join('tbl_products', 'tbl_transaction_orders.product_id', '=', 'tbl_products.product_id')
                ->join('tbl_temp', 'tbl_products.product_temp_id', '=', 'tbl_temp.temp_id')
                ->join('tbl_size', 'tbl_products.product_size_id', '=', 'tbl_size.size_id')
                ->join('tbl_category', 'tbl_products.product_category_id', '=', 'tbl_category.category_id')
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.branch_id', $branchId)
                ->where('tbl_transaction.order_status_id', 3) // subject for removal
                ->groupBy(
                    'tbl_transaction_orders.product_id',
                    'tbl_products.product_name',
                    'tbl_products.product_price',
                    'tbl_products.product_category_id',
                    'tbl_products.shop_id',
                    'tbl_products.branch_id',
                    'tbl_category.category_label',
                    'tbl_temp.temp_label',
                    'tbl_size.size_label',
                );
            if ($dateType) {
                switch ($dateType) {
                    case 1:
                        $query->whereDate('tbl_transaction_orders.updated_at', now());
                        break;
                    case 2:
                        $query->whereDate('tbl_transaction_orders.updated_at', now()->subDay());
                        break;
                    case 3:
                        $query->whereDate('tbl_transaction_orders.updated_at', '>=', now()->subDays(7));
                        break;
                    case 4:
                        $query->whereDate('tbl_transaction_orders.updated_at', '>=', now()->startOfWeek());
                        break;
                    case 5:
                        $query->whereDate('tbl_transaction_orders.updated_at', '>=', now()->subDays(30));
                        break;
                    case 6:
                        $query->whereMonth('tbl_transaction_orders.updated_at', now()->month);
                        break;
                    case 7:
                        $query->whereMonth('tbl_transaction_orders.updated_at', now()->subMonth()->month);
                        break;
                }
            }
            $totalSalesQuery = TransactionOrdersModel::select(
                DB::raw('SUM(tbl_transaction_orders.quantity * tbl_products.product_price * (1 - tbl_transaction.customer_discount/100)) as discounted_sales')
            )
                ->join('tbl_transaction', 'tbl_transaction_orders.transaction_id', '=', 'tbl_transaction.transaction_id')
                ->join('tbl_products', 'tbl_transaction_orders.product_id', '=', 'tbl_products.product_id')
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.branch_id', $branchId)
                ->where('tbl_transaction.order_status_id', 3); // subject for removal

            if ($dateType) {
                switch ($dateType) {
                    case 1:
                        $totalSalesQuery->whereDate('tbl_transaction_orders.updated_at', now());
                        break;
                    case 2:
                        $totalSalesQuery->whereDate('tbl_transaction_orders.updated_at', now()->subDay());
                        break;
                    case 3:
                        $totalSalesQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->subDays(7));
                        break;
                    case 4:
                        $totalSalesQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->startOfWeek());
                        break;
                    case 5:
                        $totalSalesQuery->whereDate('tbl_transaction_orders.updated_at', '>=', now()->subDays(30));
                        break;
                    case 6:
                        $totalSalesQuery->whereMonth('tbl_transaction_orders.updated_at', now()->month);
                        break;
                    case 7:
                        $totalSalesQuery->whereMonth('tbl_transaction_orders.updated_at', now()->subMonth()->month);
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

    public function getSalesByMonth($branchId, Request $request)
    {
        // For Analytics
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $year = $request->query('year', date('Y'));
            $dateType = $request->query('date_filter');
            $day = $request->query('day', date('d'));
            $query = TransactionModel::select(
                DB::raw('SUM(tbl_transaction.total_due) as total_sales'),
                DB::raw('MAX(tbl_transaction.updated_at) as updated_at'),
            )
                ->where('tbl_transaction.shop_id', $shopId)
                ->where('tbl_transaction.branch_id', $branchId)
                ->where('tbl_transaction.order_status_id', 3);
            if ($dateType) {
                $query->whereYear('tbl_transaction.updated_at', $year)
                    ->whereMonth('tbl_transaction.updated_at', $dateType);
            }
            $data = $query->groupBy(DB::raw('DATE(tbl_transaction.updated_at)'))
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

    public function getGrossSalesOnly($branchId, Request $request)
    {
        // For Dashboard
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $dateType = $request->query('date_filter');
            $query = TransactionModel::select(
                DB::raw('SUM(tbl_transaction.total_due) as total_sales'),
                DB::raw('MAX(tbl_transaction.updated_at) as updated_at'),
            )
                ->where('tbl_transaction.shop_id', $shopId)
                ->where('tbl_transaction.branch_id', $branchId)
                ->where('tbl_transaction.order_status_id', 3); // subject for removal
            if ($dateType) {
                $query->whereMonth('tbl_transaction.updated_at', $dateType)
                    ->whereYear('tbl_transaction.updated_at', date('Y'));
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

    /*  VOID SECTION  */

    public function getVoid($branchId, Request $request)
    {
        try {
            $shopId = Auth::guard('api')->user()->shop_id;
            $dateType = $request->query('date_filter');
            $voids = TransactionVoidModel::select(
                'tbl_transaction_void.transaction_void_id',
                'tbl_transaction_void.reference_number',
                'tbl_transaction_void.transaction_id',
                'tbl_transaction_void.table_number',
                'tbl_transaction_void.void_status_id',
                'tbl_products.product_name',
                'tbl_temp.temp_label',
                'tbl_size.size_label',
                'tbl_transaction_void.from_quantity',
                'tbl_transaction_void.to_quantity',
                'tbl_void_status.void_status',
                'tbl_transaction_void.updated_at',
            )
                ->join('tbl_products', 'tbl_transaction_void.product_id', '=', 'tbl_products.product_id')
                ->join('tbl_temp', 'tbl_products.product_temp_id', '=', 'tbl_temp.temp_id')
                ->join('tbl_size', 'tbl_products.product_size_id', '=', 'tbl_size.size_id')
                ->join('tbl_void_status', 'tbl_transaction_void.void_status_id', '=', 'tbl_void_status.void_status_id')
                ->where('tbl_transaction_void.shop_id', $shopId)
                ->where('tbl_transaction_void.branch_id', $branchId);

            // Date filter logic
            if ($dateType) {
                switch ($dateType) {
                    case 1: // Today
                        $voids->whereDate('tbl_transaction_void.updated_at', now());
                        break;
                    case 2: // Yesterday
                        $voids->whereDate('tbl_transaction_void.updated_at', now()->subDay());
                        break;
                    case 3: // Last 2 days
                        $voids->whereDate('tbl_transaction_void.updated_at', '>=', now()->subDays(2));
                        break;
                    case 4: // Last 3 days
                        $voids->whereDate('tbl_transaction_void.updated_at', '>=', now()->subDays(3));
                        break;
                    case 5: // Last 4 days
                        $voids->whereDate('tbl_transaction_void.updated_at', '>=', now()->subDays(4));
                        break;
                    case 6: // Last 5 days
                        $voids->whereDate('tbl_transaction_void.updated_at', '>=', now()->subDays(5));
                        break;
                    case 7: // Last 6 days
                        $voids->whereDate('tbl_transaction_void.updated_at', '>=', now()->subDays(6));
                        break;
                    case 8: // Last 7 days
                        $voids->whereDate('tbl_transaction_void.updated_at', '>=', now()->subDays(7));
                        break;
                }
            }
            $voids = $voids->orderBy('tbl_transaction_void.table_number', 'desc')->get();
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
            $shopId = Auth::guard('api')->user()->shop_id;
            $input = $request->all();
            $validator = Validator::make($input, [
                'referenceNumber' => 'required|string|exists:tbl_transaction_void,reference_number',
                'transactionVoidID' => 'required|integer|exists:tbl_transaction_void,transaction_void_id',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $transaction = TransactionVoidModel::where('transaction_void_id', $input['transactionVoidID'])
                ->where('reference_number', $input['referenceNumber'])
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->first();
            if (!$transaction) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }
            $currentStatus = $transaction->void_status_id;
            $nextStatus = $currentStatus % 2 + 1;
            $transaction->void_status_id = $nextStatus;
            $transaction->save();
            return response()->json([
                'status' => true,
                'message' => 'Void status updated successfully',
                'data' => [
                    'transaction_void_id' => $transaction->transaction_void_id,
                    'reference_number' => $transaction->reference_number,
                    'void_status_id' => $transaction->void_status_id,
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

    /*  OPTIONS AND PRE-DEFINED ITEMS  */
    
    public function getStockUnits()
    {
        try {
            $data = UnitModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function getProductTemperatures()
    {
        try {
            $data = TemperatureModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProductSizes()
    {
        try {
            $data = SizeModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProductCategories()
    {
        try {
            $data = CategoryModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProductAvailabilities()
    {
        try {
            $data = AvailabilityModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProductStation()
    {
        try {
            $data = StationModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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
