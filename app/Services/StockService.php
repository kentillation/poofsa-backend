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
    public static function getAllStocks($shopId, $branchId, $filters = [], $perPage = 50)
    {
        $batchSub = DB::table('tbl_stock_batches')
            ->select(
                'ingredient_id',
                DB::raw('SUM(quantity_remaining) as total_quantity'),
                DB::raw('AVG(unit_cost) as avg_unit_cost')
            )
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->groupBy('ingredient_id');

        $query = IngredientsModel::select(
            'tbl_ingredients.ingredient_id',
            'tbl_ingredients.ingredient_name',
            'tbl_ingredients.base_unit_id',
            'tbl_ingredients.alert_quantity',
            'tbl_ingredients.availability_id',
            'tbl_ingredient_unit.unit_label',
            'tbl_ingredient_unit.unit_avb',
            'tbl_availability.availability_label',
            DB::raw('COALESCE(batch.total_quantity, 0) as total_quantity'),
            DB::raw('COALESCE(batch.avg_unit_cost, 0) as avg_unit_cost')
        )
            ->leftJoinSub($batchSub, 'batch', function ($join) {
                $join->on('tbl_ingredients.ingredient_id', '=', 'batch.ingredient_id');
            })
            ->leftJoin('tbl_ingredient_unit', 'tbl_ingredients.base_unit_id', '=', 'tbl_ingredient_unit.ingredient_unit_id')
            ->leftJoin('tbl_availability', 'tbl_ingredients.availability_id', '=', 'tbl_availability.availability_id')
            ->where('tbl_ingredients.shop_id', $shopId)
            ->where('tbl_ingredients.branch_id', $branchId);

        // Apply search filters
        if (!empty($filters['ingredient_name'])) {
            $query->where('tbl_ingredients.ingredient_name', 'like', '%' . $filters['ingredient_name'] . '%');
        }

        if (!empty($filters['availability_label'])) {
            $query->where('tbl_availability.availability_label', 'like', '%' . $filters['availability_label'] . '%');
        }

        return $query->orderByDesc('tbl_ingredients.updated_at')
            ->paginate($perPage);
    }

    /**
     * Get ingredients that are low on stock
     */

    public static function lowStockService($shopId)
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
            event(new LowStockLevel($shopId, $lowStockItems));
        }

        return $lowStockItems;
    }
}
