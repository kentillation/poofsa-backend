<?php

namespace App\Services;

use App\Models\ProductsModel;
use App\Models\ProductItemsModel;
use App\Models\ProductsHistoryModel;
use App\Models\IngredientsModel;
use App\Models\TemperatureModel;
use App\Models\SizeModel;
use App\Models\CategoryModel;
use App\Models\StationModel;
use App\Models\AvailabilityModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public static function saveProductsService($request, $shopId, $userId)
    {
        $request->validate([
            '*.product_name' => 'required|string',
            '*.base_price' => 'required|numeric',
            '*.size_id' => 'required|integer',
            '*.temp_id' => 'required|integer',
            '*.category_id' => 'required|integer',
            '*.station_id' => 'required|integer',
            '*.branch_id' => 'required|integer',
        ]);

        foreach ($request->all() as $item) {
            $product = new ProductsModel();
            $product->product_name = $item['product_name'];
            $product->base_price = $item['base_price'];
            $product->size_id = $item['size_id'];
            $product->temp_id = $item['temp_id'];
            $product->category_id = $item['category_id'];
            $product->availability_id = 2;
            $product->station_id = $item['station_id'];
            $product->shop_id = $shopId;
            $product->branch_id = $item['branch_id'];
            $product->user_id = $userId;
            $product->created_at = now();
            $product->updated_at = now();
            $product->save();

            $referenceProductId = $product->product_id;
            $branchId = $product->branch_id;

            ProductsHistoryModel::create([
                'product_id' => $referenceProductId,
                'modified_type_id' => 1, // SAVE
                'description' => 'New Product Saved',
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }
    }

    public static function updateProductService($request, $productId, $shopId, $userId)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|integer',
            'product_name' => 'required|string',
            'base_price' => 'required|numeric',
            'cost_estimate' => 'required|numeric',
            'temp_id' => 'required|integer',
            'size_id' => 'required|integer',
            'category_id' => 'required|integer',
            'station_id' => 'required|integer',
            'availability_id' => 'required|integer',
            'shop_id' => 'required|integer',
            'branch_id' => 'required|integer',
        ]);
        $branchId = $validatedData['branch_id'];

        $result = DB::transaction(function () use ($validatedData, $productId, $shopId, $branchId, $userId) {

            $product = ProductsModel::findOrFail($productId);
            $originalValues = $product->getOriginal();

            if ($validatedData['availability_id'] == 1 && $originalValues['availability_id'] != 1) {
                $ingredientStockIds = ProductItemsModel::where('product_id', $productId)
                    ->where('shop_id', $shopId)
                    ->where('branch_id', $branchId)
                    ->pluck('ingredient_id')
                    ->toArray();
                if (!empty($ingredientStockIds)) {
                    $unavailableStocks = IngredientsModel::whereIn('ingredient_id', $ingredientStockIds)
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

            $product->fill($validatedData);
            $dirtyFields = $product->getDirty();
            $changes = [];

            foreach ($dirtyFields as $field => $newValue) {
                if ($field === 'updated_at') continue;
                $changes[$field] = [
                    'from' => $originalValues[$field] ?? null,
                    'to' => $newValue
                ];
            }

            $product->save();
            $product = $product->fresh([
                'temperature',
                'category',
                'availability',
                'stations'
            ]);

            $description = '';
            foreach ($changes as $field => $change) {
                $temps = TemperatureModel::pluck('temp_label', 'product_temp_id');
                $sizes = SizeModel::pluck('size_label', 'product_size_id');
                $categories = CategoryModel::pluck('category_label', 'product_category_id');
                $stations = StationModel::pluck('station_name', 'shop_station_id');
                $availabilities = AvailabilityModel::pluck('availability_label', 'availability_id');

                if ($field === 'product_name') {
                    $description .= "Product name: From [{$change['from']}] To [{$change['to']}]. ";
                } elseif ($field === 'base_price') {
                    $description .= "Base price: From [₱{$change['from']}] To [₱{$change['to']}]. ";
                } elseif ($field === 'cost_estimate') {
                    $description .= "Estimated cost: From [₱{$change['from']}] To [₱{$change['to']}]. ";
                } elseif ($field === 'temp_id') {
                    $fromLabel = $temps[$change['from']] ?? $change['from'];
                    $toLabel = $temps[$change['to']] ?? $change['to'];
                    $description .= "Temperature: From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'size_id') {
                    $fromLabel = $sizes[$change['from']] ?? $change['from'];
                    $toLabel = $sizes[$change['to']] ?? $change['to'];
                    $description .= "Size: From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'category_id') {
                    $fromLabel = $categories[$change['from']] ?? $change['from'];
                    $toLabel = $categories[$change['to']] ?? $change['to'];
                    $description .= "Category: From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'station_id') {
                    $fromLabel = $stations[$change['from']] ?? $change['from'];
                    $toLabel = $stations[$change['to']] ?? $change['to'];
                    $description .= "Station    : From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'availability_id') {
                    $fromLabel = $availabilities[$change['from']] ?? $change['from'];
                    $toLabel = $availabilities[$change['to']] ?? $change['to'];
                    $description .= "Availability: From [{$fromLabel}] To [{$toLabel}]. ";
                } else {
                    $description .= ucfirst(str_replace('_', ' ', $field)) . ": From [{$change['from']}] To [{$change['to']}]. ";
                }
            }

            if (empty($description)) {
                $description = 'No fields were updated';
            }

            $referenceProductId = $product->product_id;

            ProductsHistoryModel::create([
                'product_id' => $referenceProductId,
                'modified_type_id' => 2, // UPDATE
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'description' => trim($description),
            ]);

            return [
                'product' => $product,
                'changes' => $changes
            ];
        });

        return $result;
    }

    public static function getProductsService($shopId, $branchId, $search, $page, $perPage)
    {
        $query = ProductsModel::with(['temperature', 'size', 'category', 'stations', 'availability'])
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('temperature', function ($q2) use ($search) {
                        $q2->where('temp_label', 'like', "%{$search}%");
                    })
                    ->orWhereHas('size', function ($q2) use ($search) {
                        $q2->where('size_label', 'like', "%{$search}%");
                    })
                    ->orWhereHas('category', function ($q2) use ($search) {
                        $q2->where('category_label', 'like', "%{$search}%");
                    })
                    ->orWhereHas('stations', function ($q2) use ($search) {
                        $q2->where('station_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('availability', function ($q2) use ($search) {
                        $q2->where('availability_label', 'like', "%{$search}%");
                    });
            });
        }

        $total = $query->count();

        $products = $query->orderByDesc('updated_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Map products for frontend display
        $mapped = $products->map(function ($product) {
            return [
                'shop_id' => $product->shop_id,
                'branch_id' => $product->branch_id,
                'product_id' => $product->product_id,
                'temp_id' => $product->temp_id,
                'size_id' => $product->size_id,
                'category_id' => $product->category_id,
                'station_id' => $product->station_id,
                'availability_id' => $product->availability_id,
                'product_name' => $product->product_name,
                'base_price' => $product->base_price,
                'cost_estimate' => $product->cost_estimate,
                'temp_label' => $product->temperature->temp_label ?? null,
                'size_label' => $product->size->size_label ?? null,
                'category_label' => $product->category->category_label ?? null,
                'station_name' => $product->stations->station_name ?? null,
                'availability_label' => $product->availability->availability_label ?? null,
                'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'mapped' => $mapped,
            'total' => $total,
        ];
    }

    public static function getProductsHistoryService($shopId, $branchId, $search = '', $page = 1, $perPage = 10)
    {
        // $products = ProductsHistoryModel::select(
        //     'tbl_products.product_name',
        //     'tbl_products_history.modified_type_id',
        //     'tbl_products_history.description',
        //     'tbl_admin.admin_name',
        //     'tbl_products_history.updated_at',
        // )
        //     ->join('tbl_products', 'tbl_products_history.product_id', '=', 'tbl_products.product_id')
        //     ->join('tbl_admin', 'tbl_products_history.user_id', '=', 'tbl_admin.admin_id')
        //     ->where('tbl_products_history.shop_id', $shopId)
        //     ->where('tbl_products_history.branch_id', $branchId)
        //     ->orderBy('tbl_products_history.updated_at')
        //     ->get();

        $query = ProductsHistoryModel::select(
            'tbl_products.product_name',
            'tbl_products_history.modified_type_id',
            'tbl_products_history.description',
            'tbl_admin.admin_name',
            'tbl_products_history.updated_at',
        )
            ->join('tbl_products', 'tbl_products_history.product_id', '=', 'tbl_products.product_id')
            ->join('tbl_admin', 'tbl_products_history.user_id', '=', 'tbl_admin.admin_id')
            ->where('tbl_products_history.shop_id', $shopId)
            ->where('tbl_products_history.branch_id', $branchId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('tbl_products.product_name', 'like', "%{$search}%")
                    ->orWhere('tbl_admin.admin_name', 'like', "%{$search}%")
                    ->orWhere('tbl_products_history.description', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $mapped = $query->orderByDesc('updated_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'mapped' => $mapped,
            'total' => $total,
        ];
    }

    public static function getTotalProductsCountService($shopId, $branchId)
    {
        $totalProducts = ProductsModel::select(
            DB::raw('COUNT(tbl_products.product_id) as total_products')
        )
            ->where('tbl_products.shop_id', $shopId)
            ->where('tbl_products.branch_id', $branchId)
            ->first();

        return $totalProducts;
    }

    public static function getProductItemsService($shopId, $productId)
    {
        $productItems = ProductItemsModel::select(
            'tbl_product_items.product_item_id',
            'tbl_product_items.product_id',
            'tbl_product_items.ingredient_id',
            'tbl_product_items.quantity_required',
            'tbl_product_items.ingredient_capital',
            'tbl_product_items.updated_at',
            'tbl_products.product_name',
            'tbl_product_temp.temp_label',
            'tbl_product_size.size_label',
            'tbl_ingredients.ingredient_id',
            'tbl_ingredients.branch_id',
            'tbl_ingredients.ingredient_name',
            'tbl_availability.availability_label',
            'tbl_ingredient_unit.unit_avb',
        )
            ->join('tbl_products', 'tbl_product_items.product_id', '=', 'tbl_products.product_id')
            ->join('tbl_product_temp', 'tbl_products.temp_id', '=', 'tbl_product_temp.product_temp_id')
            ->join('tbl_product_size', 'tbl_products.size_id', '=', 'tbl_product_size.product_size_id')
            ->join('tbl_ingredients', 'tbl_product_items.ingredient_id', '=', 'tbl_ingredients.ingredient_id')
            ->join('tbl_availability', 'tbl_ingredients.availability_id', '=', 'tbl_availability.availability_id')
            ->join('tbl_ingredient_unit', 'tbl_ingredients.base_unit_id', '=', 'tbl_ingredient_unit.ingredient_unit_id')
            ->where('tbl_product_items.shop_id', $shopId)
            ->where('tbl_product_items.product_id', $productId)
            ->orderBy('tbl_ingredients.ingredient_name')
            ->get();

        return $productItems;
    }

    public static function updateProductItemsService($request, $productItemId, $shopId, $userId)
    {
        $validatedData = $request->validate([
            'product_item_id' => 'required|integer',
            'product_id' => 'required|integer',
            'ingredient_id' => 'required|integer',
            'quantity_required' => 'required|numeric',
            'ingredient_capital' => 'required|numeric',
        ]);

        $result = DB::transaction(function () use ($validatedData, $productItemId, $shopId, $userId) {

            $productItems = ProductItemsModel::findOrFail($productItemId);
            $originalValues = $productItems->getOriginal();

            $productItems->fill($validatedData);
            $dirtyFields = $productItems->getDirty();

            $changes = [];

            foreach ($dirtyFields as $field => $newValue) {
                if ($field === 'updated_at') continue;
                $changes[$field] = [
                    'from' => $originalValues[$field] ?? null,
                    'to' => $newValue
                ];
            }

            $productItems->save();
            $productItems = $productItems->fresh([
                'product',
                'ingredient',
            ]);

            $description = '';
            foreach ($changes as $field => $change) {
                $ingredients = IngredientsModel::pluck('ingredient_id', 'ingredient_name');

                if ($field === 'ingredient_id') {
                    $fromLabel = $ingredients[$change['from']] ?? $change['from'];
                    $toLabel = $ingredients[$change['to']] ?? $change['to'];
                    $description .= "Product Items: From [{$fromLabel}] To [{$toLabel}]. ";
                } elseif ($field === 'quantity_required') {
                    $description .= "Quantity required: From [{$change['from']}] To [{$change['to']}]. ";
                } elseif ($field === 'ingredient_capital') {
                    $description .= "Ingredient capital: From [₱{$change['from']}] To [₱{$change['to']}]. ";
                } else {
                    $description .= ucfirst(str_replace('_', ' ', $field)) . ": From [{$change['from']}] To [{$change['to']}]. ";
                }
            }

            if (empty($description)) {
                $description = 'No fields were updated';
            }

            $referenceProductId = $productItems->product_id;
            $branchId = $productItems->branch_id;

            ProductsHistoryModel::create([
                'product_id' => $referenceProductId,
                'modified_type_id' => 2, // UPDATE
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'description' => trim($description),
            ]);

            return [
                'productItems' => $productItems,
                'changes' => $changes
            ];
        });

        return $result;
    }
}
