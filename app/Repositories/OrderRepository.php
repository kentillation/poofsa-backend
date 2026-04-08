<?php

namespace App\Repositories;

use App\Models\OrdersModel;

class OrderRepository
{
    public function getOrders($shopId, $branchId, $search, $perPage)
    {
        $perPage = (int) $perPage ?: 10;

        return OrdersModel::with([
            'orderType',
            'orderStatus',
            'sale.paymentMethod',
            'sale.salesStatus'
        ])
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->when($search, function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas(
                        'orderType',
                        fn($q) =>
                        $q->where('order_type', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'orderStatus',
                        fn($q) =>
                        $q->where('order_status', 'like', "%{$search}%")
                    );
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function getOrdersReport($shopId, $branchId, $dateType, $perPage)
    {
        $query = OrdersModel::with([
            'orderType',
            'orderStatus',
            'sale.paymentMethod',
            'sale.salesStatus',
            'cashier'
        ])
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->where('order_status_id', 3)
            ->orderByDesc('updated_at');

        if ($dateType) {
            switch ($dateType) {
                case 1: // Today
                    $query->whereDate('updated_at', now());
                    break;
                case 2: // Yesterday
                    $query->whereDate('updated_at', now()->subDay());
                    break;
                case 3: // Last 7 days
                    $query->whereDate('updated_at', '>=', now()->subDays(7));
                    break;
                case 4: // This week
                    $query->whereDate('updated_at', '>=', now()->startOfWeek());
                    break;
                case 5: // Last week
                    $query->whereDate('updated_at', '>=', now()->subWeek());
                    break;
                case 6: // Last 30 days
                    $query->whereDate('updated_at', '>=', now()->subDays(30));
                    break;
                case 7: // This month
                    $query->whereMonth('updated_at', now()->month);
                    break;
                case 8: // Last month
                    $query->whereMonth('updated_at', now()->subMonth()->month);
                    break;
                case 9: // This Year
                    $query->whereMonth('updated_at', now()->year);
                    break;
                case 10: // Last Year
                    $query->whereMonth('updated_at', now()->subYear()->year);
                    break;
                default:
                    break;
            }
        }
        return $query->paginate($perPage);
    }

    public function getTotalOrders($shopId, $branchId)
    {
        $query = OrdersModel::where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->where('order_status_id', 3);
            // ->whereMonth('updated_at', now()->month);

        return $query->count();
    }
}

// This Repository is for Orders module only
