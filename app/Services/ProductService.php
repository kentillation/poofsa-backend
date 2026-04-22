<?php

namespace App\Services;

use App\Models\ProductsModel;
use App\Models\ProductItemsModel;
use App\Models\ProductsHistoryModel;
use App\Models\IngredientsModel;
use App\Models\CategoryModel;
use App\Models\ProductBaseCategoryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductService
{

    public static function saveProductsService($request, $shopId, $userId)
    {
        DB::beginTransaction();

        try {
            // Handle FormData input (products array with optional images)
            $productsInput = $request->input('products');

            if (!$productsInput || !is_array($productsInput)) {
                throw new \Exception('Invalid products data');
            }

            $saved = [];
            $skipped = [];

            foreach ($productsInput as $index => $item) {
                try {
                    // Validate required fields
                    if (empty($item['product_name']) || empty($item['base_price'])) {
                        $skipped[] = [
                            'index' => $index,
                            'product_name' => $item['product_name'] ?? 'Unknown',
                            'reason' => 'Missing required fields'
                        ];
                        continue;
                    }

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
                        continue;
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

                    // Handle image upload if present
                    $thumbnailPath = null;
                    $standardPath = null;
                    $imageSizeKb = null;

                    $thumbnailPath = null;
                    $standardPath = null;
                    $imageSizeKb = null;

                    if ($request->hasFile("products.{$index}.image")) {
                        try {
                            $imageFile = $request->file("products.{$index}.image");

                            // Initialize image optimizer and process image
                            $imageOptimizer = new ImageOptimizerService();
                            $imageData = $imageOptimizer->optimizeAndSave($imageFile, uniqid());

                            $thumbnailPath = $imageData['thumbnail_path'];
                            $standardPath = $imageData['standard_path'];
                            $imageSizeKb = $imageData['size_kb'];
                        } catch (\Exception $e) {
                            // Log error but continue saving the product without image
                            Log::warning('Image processing failed for product, continuing without image', [
                                'product_name' => $item['product_name'],
                                'error' => $e->getMessage()
                            ]);
                            // Don't throw - just continue without image
                        }
                    }

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
                        'thumbnail_path' => $thumbnailPath,
                        'standard_image_path' => $standardPath,
                        'image_size_kb' => $imageSizeKb,
                    ]);

                    // Save history
                    ProductsHistoryModel::create([
                        'product_id' => $product->product_id,
                        'modified_type_id' => 1,
                        'description' => 'New Product Saved' . ($thumbnailPath ? ' with image' : ''),
                        'shop_id' => $shopId,
                        'branch_id' => $product->branch_id,
                        'user_id' => $userId,
                    ]);

                    $saved[] = $product;
                } catch (\Throwable $e) {
                    Log::error('Product Save Item Error', [
                        'index' => $index,
                        'item' => $item,
                        'error' => $e->getMessage(),
                    ]);

                    $skipped[] = [
                        'index' => $index,
                        'product_name' => $item['product_name'] ?? null,
                        'reason' => 'Unexpected error: ' . $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => count($saved) . ' ' . (count($saved) > 1 ? 'products' : 'product') . ' saved successfully' . (count($skipped) > 0 ? ', ' . count($skipped) . ' skipped' : ''),
                'saved_count' => count($saved),
                'skipped_count' => count($skipped),
                'saved' => $saved,
                'skipped' => $skipped,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Save Products Fatal Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process products: ' . $e->getMessage(),
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

    // ProductService.php

    public static function updateProductService($request, $productId, $shopId, $userId)
    {
        DB::beginTransaction();

        try {
            // Handle both JSON and FormData requests
            if ($request->hasFile('image')) {
                // FormData with image
                $productData = [
                    'product_name' => $request->input('product_name'),
                    'base_price' => $request->input('base_price'),
                    'cost_estimate' => $request->input('cost_estimate', 0),
                    'temp_id' => $request->input('temp_id'),
                    'size_id' => $request->input('size_id'),
                    'category_id' => $request->input('category_id'),
                    'station_id' => $request->input('station_id'),
                    'availability_id' => $request->input('availability_id'),
                ];
                $hasImage = true;
                $removeImage = $request->input('remove_image') === 'true';
            } else {
                // Regular JSON request
                $productData = $request->json()->all();
                $hasImage = false;
                $removeImage = false;
            }

            // Find product
            $product = ProductsModel::where('product_id', $productId)
                ->where('shop_id', $shopId)
                ->first();

            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Check for duplicates (excluding current product)
            $exists = ProductsModel::where('product_name', $productData['product_name'])
                ->where('size_id', $productData['size_id'])
                ->where('temp_id', $productData['temp_id'])
                ->where('shop_id', $shopId)
                ->where('product_id', '!=', $productId)
                ->exists();

            if ($exists) {
                throw new \Exception('Duplicate product exists with same name, size, and temperature');
            }

            // Handle image upload
            $thumbnailPath = $product->thumbnail_path;
            $standardPath = $product->standard_image_path;
            $imageSizeKb = $product->image_size_kb;

            if ($removeImage) {
                // Delete existing images
                if ($product->thumbnail_path) {
                    Storage::disk('public')->delete($product->thumbnail_path);
                }
                if ($product->standard_image_path) {
                    Storage::disk('public')->delete($product->standard_image_path);
                }
                $thumbnailPath = null;
                $standardPath = null;
                $imageSizeKb = null;
            } elseif ($hasImage && $request->hasFile('image')) {
                // Delete old images
                if ($product->thumbnail_path) {
                    Storage::disk('public')->delete($product->thumbnail_path);
                }
                if ($product->standard_image_path) {
                    Storage::disk('public')->delete($product->standard_image_path);
                }

                // Upload new images
                $imageFile = $request->file('image');
                $imageOptimizer = new ImageOptimizerService();
                $imageData = $imageOptimizer->optimizeAndSave($imageFile, uniqid());

                $thumbnailPath = $imageData['thumbnail_path'];
                $standardPath = $imageData['standard_path'];
                $imageSizeKb = $imageData['size_kb'];
            }

            // Update product
            $product->update([
                'product_name' => $productData['product_name'],
                'base_price' => $productData['base_price'],
                'cost_estimate' => $productData['cost_estimate'] ?? 0,
                'temp_id' => $productData['temp_id'],
                'size_id' => $productData['size_id'],
                'category_id' => $productData['category_id'],
                'station_id' => $productData['station_id'],
                'availability_id' => $productData['availability_id'],
                'thumbnail_path' => $thumbnailPath,
                'standard_image_path' => $standardPath,
                'image_size_kb' => $imageSizeKb,
            ]);

            // Save history
            ProductsHistoryModel::create([
                'product_id' => $product->product_id,
                'modified_type_id' => 2, // 2 for update
                'description' => $hasImage ? 'Product updated with new image' : 'Product updated',
                'shop_id' => $shopId,
                'branch_id' => $product->branch_id,
                'user_id' => $userId,
            ]);

            DB::commit();

            // Load relationships for response
            $product->load(['temperature', 'size', 'category', 'stations', 'availability']);

            return [
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
                'changes' => [
                    'has_image_change' => $hasImage || $removeImage,
                    'image_updated' => $hasImage,
                    'image_removed' => $removeImage
                ]
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Update Product Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
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
