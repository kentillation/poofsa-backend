<?php

namespace App\Services;

use App\Models\IngredientsModel;
use App\Models\StockBatchesModel;
use App\Events\LowStockLevel;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Get all stocks with optional search/filter
     */
    public static function getAllStocksService($shopId, $branchId, $search, $page, $perPage)
    {
        // $batchSub = DB::table('tbl_stock_batches')
        //     ->select(
        //         'ingredient_id',
        //         DB::raw('SUM(quantity_remaining) as total_quantity'),
        //         DB::raw('AVG(unit_cost) as avg_unit_cost')
        //     )
        //     ->where('shop_id', $shopId)
        //     ->where('branch_id', $branchId)
        //     ->groupBy('ingredient_id');

        $query = IngredientsModel::with(['unit', 'availability'])
            ->pluck('batches')
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('ingredient_name', 'like', "%{$search}%")
                    ->orWhereHas('unit', function ($q2) use ($search) {
                        $q2->where('unit_label', 'like', "%{$search}%");
                    })
                    ->orWhereHas('availability', function ($q2) use ($search) {
                        $q2->where('availability_label', 'like', "%{$search}%");
                    });
            });
        }

        $total = $query->count();

        $stocks = $query->orderByDesc('updated_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Map products for frontend display
        $mapped = $stocks->map(function ($stock) {
            return [
                'shop_id' => $stock->shop_id,
                'branch_id' => $stock->branch_id,
                'ingredient_id' => $stock->ingredient_id,
                'ingredient_name' => $stock->ingredient_name,
                'base_unit_id' => $stock->base_unit_id,
                'quantity_received' => $stock->batches->quantity_received,
                'quantity_remaining' => $stock->batches->quantity_remaining,
                'alert_quantity' => $stock->alert_quantity,
                'availability_id' => $stock->availability_id,
                'availability_label' => $stock->availability->availability_label ?? null,
                'unit_label' => $stock->unit->unit_label ?? null,
                'unit_avb' => $stock->unit->unit_avb ?? null,
                'updated_at' => $stock->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'mapped' => $mapped,
            'total' => $total,
        ];
    }

    /**
     * Get ingredients that are low on stock
     */
    public static function lowStockService($shopId, $branchId)
    {
        $lowStockItems = StockBatchesModel::select(
            'tbl_shop_branch.branch_name',
            'tbl_stock_batches.branch_id',
            'tbl_stock_batches.ingredient_id',
            'tbl_ingredients.ingredient_name',
            'tbl_ingredients.alert_quantity',
            DB::raw('SUM(tbl_stock_batches.quantity_remaining) as total_remaining')
        )
            ->join('tbl_ingredients', 'tbl_stock_batches.ingredient_id', '=', 'tbl_ingredients.ingredient_id')
            ->join('tbl_shop_branch', 'tbl_stock_batches.branch_id', '=', 'tbl_shop_branch.branch_id')
            ->where('tbl_stock_batches.shop_id', $shopId)
            ->where('tbl_stock_batches.branch_id', $branchId)
            ->groupBy(
                'tbl_shop_branch.branch_name',
                'tbl_stock_batches.branch_id',
                'tbl_stock_batches.ingredient_id',
                'tbl_ingredients.ingredient_name',
                'tbl_ingredients.alert_quantity'
            )
            ->havingRaw('SUM(tbl_stock_batches.quantity_remaining) <= tbl_ingredients.alert_quantity')
            ->get();

        if ($lowStockItems->isNotEmpty()) {
            event(new LowStockLevel($shopId, $branchId, $lowStockItems));
        }

        return $lowStockItems;
    }
}
