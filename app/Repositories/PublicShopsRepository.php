<?php

namespace App\Repositories;

use App\Models\ShopModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicShopsRepository
{
    public function getAllPublicShops($categoryLabel, $mealType, $timeBetween, $perPage, $search = null)
    {
        // Validate perPage
        $perPage = $perPage > 0 ? $perPage : 10;
        $currentPage = request()->get('page', 1);

        // Build the base query for shops WITHOUT the whereHas condition
        $shopQuery = ShopModel::query()
            ->when($timeBetween, function ($query) use ($timeBetween) {
                $formattedTime = date('H:i:s', strtotime($timeBetween));
                $query->whereTime('open_at', '<=', $formattedTime)
                    ->whereTime('close_at', '>=', $formattedTime);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shop_type', 'LIKE', "%{$search}%");
                });
            });
        // REMOVED: ->whereHas('products', ...)

        // Get total count
        $total = $shopQuery->count();
        Log::info('Total shops: ' . $total);

        // Get paginated shop IDs
        $shopIds = $shopQuery
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->pluck('shop_id')
            ->toArray();

        Log::info('Shop IDs: ' . json_encode($shopIds));

        if (empty($shopIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query()
            ]);
        }

        // Get shops with their products
        $results = collect();

        foreach ($shopIds as $shopId) {
            $shop = ShopModel::find($shopId);

            if (!$shop) {
                continue;
            }

            // Try to get a product, but don't require it
            $productQuery = DB::table('tbl_products')
                ->where('shop_id', $shopId);

            // Apply category and meal type filters if provided
            if ($categoryLabel || $mealType) {
                $productQuery->leftJoin('tbl_product_category as c', 'tbl_products.category_id', '=', 'c.product_category_id');

                if ($categoryLabel) {
                    $productQuery->where('c.category_label', $categoryLabel);
                }

                if ($mealType) {
                    $productQuery->leftJoin('tbl_base_category as bc', 'c.base_category_id', '=', 'bc.base_category_id')
                        ->whereRaw('JSON_CONTAINS(bc.meal_type, ?)', [json_encode($mealType)]);
                }
            }

            $lowestProduct = $productQuery
                ->orderBy('tbl_products.base_price', 'asc')
                ->first();

            // Always include the shop, even without products
            $results->push([
                'shop_id' => $shop->shop_id,
                'branch_id' => $lowestProduct->branch_id ?? null,
                'shop_name' => $shop->shop_name,
                'shop_type' => $shop->shop_type,
                'lowest_price' => $lowestProduct->base_price ?? null,
                'product_name' => $lowestProduct->product_name ?? null,
                'product_id' => $lowestProduct->product_id ?? null,
                'category_label' => $lowestProduct->category_label ?? null,
            ]);
        }

        Log::info('Final results count: ' . $results->count());

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
