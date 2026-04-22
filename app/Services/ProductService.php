<?php

namespace App\Services;

use App\Models\ProductsModel;
use App\Models\ProductItemsModel;
use App\Models\ProductsHistoryModel;
use App\Models\IngredientsModel;
use App\Models\TemperatureModel;
use App\Models\SizeModel;
use App\Models\CategoryModel;
use App\Models\ProductBaseCategoryModel;
use App\Models\StationModel;
use App\Models\AvailabilityModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{

    public static function saveProductsService($request, $shopId, $userId)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                '*.product_name' => 'required|string',
                '*.base_price' => 'required|numeric',
                '*.size_id' => 'required|integer',
                '*.temp_id' => 'required|integer',
                '*.category_id' => 'required|integer',
                '*.station_id' => 'required|integer',
                '*.branch_id' => 'required|integer',
            ]);

            $saved = [];
            $skipped = [];

            foreach ($validated as $index => $item) {

                try {
                    // Check duplicate
                    $exists = ProductsModel::where('product_name', $item['product_name'])
                        ->where('size_id', $item['size_id'])
                        ->where('temp_id', $item['temp_id'])
                        ->where('shop_id', $shopId)
                        ->exists();

                    if ($exists) {
                        $skipped[] = [
                            'index' => $index,
                            'product_name' => $item['product_name'],
                            'reason' => 'Duplicate product (name + size + temp)'
                        ];
                        continue; // Skip, don't stop
                    }

                    // Check base category
                    $baseCategory = ProductBaseCategoryModel::find($item['category_id']);

                    if (!$baseCategory) {
                        $skipped[] = [
                            'index' => $index,
                            'product_name' => $item['product_name'],
                            'reason' => "Category ID {$item['category_id']} not found"
                        ];
                        continue;
                    }

                    // Create or find category
                    $category = CategoryModel::firstOrCreate(
                        [
                            'product_base_category_id' => $baseCategory->product_base_category_id,
                            'shop_id' => $shopId,
                        ],
                        [
                            'category_label' => $baseCategory->product_base_category,
                        ]
                    );

                    // Save product
                    $product = ProductsModel::create([
                        'product_name' => $item['product_name'],
                        'base_price' => $item['base_price'],
                        'cost_estimate' => 0,
                        'size_id' => $item['size_id'],
                        'temp_id' => $item['temp_id'],
                        'category_id' => $category->product_category_id,
                        'availability_id' => 1,
                        'station_id' => $item['station_id'],
                        'shop_id' => $shopId,
                        'branch_id' => $item['branch_id'],
                        'user_id' => $userId,
                    ]);

                    // Save history
                    ProductsHistoryModel::create([
                        'product_id' => $product->product_id,
                        'modified_type_id' => 1,
                        'description' => 'New Product Saved',
                        'shop_id' => $shopId,
                        'branch_id' => $product->branch_id,
                        'user_id' => $userId,
                    ]);

                    $saved[] = $product;
                } catch (\Throwable $e) {
                    // Catch per-item errors (DO NOT break loop)
                    Log::error('Product Save Item Error', [
                        'index' => $index,
                        'item' => $item,
                        'error' => $e->getMessage(),
                    ]);

                    $skipped[] = [
                        'index' => $index,
                        'product_name' => $item['product_name'] ?? null,
                        'reason' => 'Unexpected error'
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Products processed',
                'saved_count' => count($saved),
                'skipped_count' => count($skipped),
                'saved' => $saved,
                'skipped' => $skipped,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Save Products Fatal Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process products',
            ];
        }
    }

    // public static function saveProductsService($request, $shopId, $userId)
    // {
    //     $request->validate([
    //         '*.product_name' => 'required|string',
    //         '*.base_price' => 'required|numeric',
    //         '*.size_id' => 'required|integer',
    //         '*.temp_id' => 'required|integer',
    //         '*.category_id' => 'required|integer',
    //         '*.station_id' => 'required|integer',
    //         '*.branch_id' => 'required|integer',
    //     ]);

    //     foreach ($request->all() as $item) {
    //         $baseCategory = ProductBaseCategoryModel::find($item['category_id']);

    //         if (!$baseCategory) {
    //             throw new \Exception("Category with ID {$item['category_id']} not found");
    //         }

    //         $category = CategoryModel::firstOrCreate(
    //             [
    //                 'product_base_category_id' => $baseCategory->product_base_category_id,
    //                 'shop_id' => $shopId,
    //             ],
    //             [
    //                 'category_label' => $baseCategory->product_base_category,
    //                 'product_base_category_id' => $baseCategory->product_base_category_id,
    //                 'shop_id' => $shopId,
    //             ]
    //         );

    //         $product = new ProductsModel();
    //         $product->product_name = $item['product_name'];
    //         $product->base_price = $item['base_price'];
    //         $product->cost_estimate = 0;
    //         $product->size_id = $item['size_id'];
    //         $product->temp_id = $item['temp_id'];
    //         $product->category_id = $category->product_category_id;
    //         $product->availability_id = 1;
    //         $product->station_id = $item['station_id'];
    //         $product->shop_id = $shopId;
    //         $product->branch_id = $item['branch_id'];
    //         $product->user_id = $userId;

    //         $product->save();

    //         ProductsHistoryModel::create([
    //             'product_id' => $product->product_id,
    //             'modified_type_id' => 1, // SAVE
    //             'description' => 'New Product Saved',
    //             'shop_id' => $shopId,
    //             'branch_id' => $product->branch_id,
    //             'user_id' => $userId,
    //         ]);
    //     }

    //     return true;
    // }

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
