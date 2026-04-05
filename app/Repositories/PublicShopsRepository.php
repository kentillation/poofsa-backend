<?php

namespace App\Repositories;

use App\Models\ShopModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PublicShopsRepository
{
    public function getAllPublicShops($categoryLabel, $mealType, $timeBetween, $perPage = 10, $search = null)
    {
        // Step 1: Get only 10 shop IDs
        $shopIds = ShopModel::query()
            ->when($timeBetween, function ($query) use ($timeBetween) {
                $query->whereTime('open_at', '<=', $timeBetween)
                    ->whereTime('close_at', '>=', $timeBetween);
            })
            ->whereHas('products', function (Builder $query) use ($categoryLabel, $mealType) {
                $query->where('availability_id', 1);

                if ($categoryLabel) {
                    $query->whereHas('category', function (Builder $category) use ($categoryLabel) {
                        $category->where('category_label', $categoryLabel);
                    });
                }

                if ($mealType) {
                    $query->whereHas('category.baseCategory', function (Builder $base) use ($mealType) {
                        $base->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
                    });
                }
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shop_type', 'LIKE', "%{$search}%");
                });
            })
            ->limit($perPage)
            ->pluck('shop_id')
            ->toArray();

        if (empty($shopIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1);
        }

        // Step 2: Get shops
        $shops = ShopModel::whereIn('shop_id', $shopIds)
            ->get()
            ->keyBy('shop_id');

        // Step 3: Get lowest priced product for each shop (WITH category filter)
        // Step 3: Get lowest priced product for each shop (WITH category filter)
        // First, get the minimum price per shop with filters applied
        $minPricesSubquery = DB::table('tbl_products as p')
            ->select('p.shop_id', DB::raw('MIN(p.base_price) as min_price'))
            ->leftJoin('tbl_product_category as c', 'p.category_id', '=', 'c.product_category_id')
            ->whereIn('p.shop_id', $shopIds)
            ->where('p.availability_id', 1)
            ->when($categoryLabel, function ($query) use ($categoryLabel) {
                $query->where('c.category_label', $categoryLabel);
            })
            ->when($mealType, function ($query) use ($mealType) {
                $query->whereHas('category.baseCategory', function (Builder $base) use ($mealType) {
                    $base->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
                });
            })
            ->groupBy('p.shop_id');

        // Then get the actual product details by joining with the min prices
        $lowestProducts = DB::table('tbl_products as p')
            ->select(
                'p.shop_id',
                'p.product_id',
                'p.product_name',
                'p.base_price',
                'p.branch_id',
                'c.category_label'
            )
            ->leftJoin('tbl_product_category as c', 'p.category_id', '=', 'c.product_category_id')
            ->joinSub($minPricesSubquery, 'min_prices', function ($join) {
                $join->on('p.shop_id', '=', 'min_prices.shop_id')
                    ->on('p.base_price', '=', 'min_prices.min_price');
            })
            ->whereIn('p.shop_id', $shopIds)
            ->where('p.availability_id', 1)
            // Apply filters again to ensure the product matches
            ->when($categoryLabel, function ($query) use ($categoryLabel) {
                $query->where('c.category_label', $categoryLabel);
            })
            ->when($mealType, function ($query) use ($mealType) {
                $query->whereHas('category.baseCategory', function (Builder $base) use ($mealType) {
                    $base->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
                });
            })
            ->get()
            ->keyBy('shop_id');

        // Step 4: Combine results
        $results = collect();
        foreach ($shopIds as $shopId) {
            $shop = $shops->get($shopId);
            $product = $lowestProducts->get($shopId);

            if ($shop && $product) {
                $results->push([
                    'shop_id' => $shop->shop_id,
                    'branch_id' => $product->branch_id ?? null,
                    'shop_name' => $shop->shop_name,
                    'shop_type' => $shop->shop_type,
                    'lowest_price' => $product->base_price,
                    'product_name' => $product->product_name,
                    'product_id' => $product->product_id,
                    'category_label' => $product->category_label ?? null,
                ]);
            }
        }

        // Step 5: Get total count
        $total = ShopModel::query()
            ->when($timeBetween, function ($query) use ($timeBetween) {
                $query->whereTime('open_at', '<=', $timeBetween)
                    ->whereTime('close_at', '>=', $timeBetween);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shop_type', 'LIKE', "%{$search}%");
                });
            })
            ->whereHas('products', function (Builder $query) use ($categoryLabel, $mealType) {
                $query->where('availability_id', 1);

                if ($categoryLabel) {
                    $query->whereHas('category', function (Builder $category) use ($categoryLabel) {
                        $category->where('category_label', $categoryLabel);
                    });
                }

                if ($mealType) {
                    $query->whereHas('category.baseCategory', function (Builder $base) use ($mealType) {
                        $base->whereRaw('JSON_CONTAINS(meal_type, ?)', [json_encode($mealType)]);
                    });
                }
            })
            ->count();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            request()->get('page', 1),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
