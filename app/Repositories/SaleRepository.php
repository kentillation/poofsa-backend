<?php

namespace App\Repositories;

use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;

class SaleRepository
{
    public function getTotalSales($shopId, $branchId)
    {
        $query = SalesModel::select(
                DB::raw('SUM(tbl_sales.total_amount) as total_sales')
            )
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->where('sales_status_id', 1)
            ->first();

        return $query ? $query->total_sales : 0;
    }
}

// This Repository is for Sales module only
