<?php

namespace App\Repositories;

use App\Models\IngredientsModel;
use App\Models\StocksHistoryModel;

class StockRepository
{
    public function getStocks($shopId, $branchId, $search, $perPage)
    {
        $perPage = (int) $perPage ?: 20;

        return IngredientsModel::with(['unit', 'availability'])
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->when($search, function ($q) use ($search) {
                $q->where('ingredient_name', 'like', "%{$search}%")
                    ->orWhereHas('unit', function ($q2) use ($search) {
                        $q2->where('unit_label', 'like', "%{$search}%");
                    })
                    ->orWhereHas('availability', function ($q2) use ($search) {
                        $q2->where('availability_label', 'like', "%{$search}%");
                    });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage ?? 20);
    }

    public function getStocksHistory($shopId, $branchId, $search, $perPage) {
        return StocksHistoryModel::select(
            'tbl_ingredients.ingredient_name',
            'tbl_stocks_history.modified_type_id',
            'tbl_modified_type.modified_type',
            'tbl_stocks_history.description',
            'tbl_admin.admin_name',
            'tbl_stocks_history.updated_at',
        )
            ->join('tbl_modified_type', 'tbl_stocks_history.modified_type_id', '=', 'tbl_modified_type.modified_type_id')
            ->join('tbl_ingredients', 'tbl_stocks_history.ingredient_id', '=', 'tbl_ingredients.ingredient_id')
            ->join('tbl_admin', 'tbl_stocks_history.user_id', '=', 'tbl_admin.admin_id')
            ->where('tbl_stocks_history.shop_id', $shopId)
            ->where('tbl_stocks_history.branch_id', $branchId)
            ->when($search, function ($q) use ($search) {
                $q->where('tbl_ingredients.ingredient_name', 'like', "%{$search}%")
                    ->orWhere('tbl_admin.admin_name', 'like', "%{$search}%")
                    ->orWhere('tbl_stocks_history.description', 'like', "%{$search}%");
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }
}

// This Repository is for Stocks module only
