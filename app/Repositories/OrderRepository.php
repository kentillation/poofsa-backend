<?php

namespace App\Repositories;

use App\Models\OrdersModel;

class OrderRepository
{
    public function getOrders($shopId, $branchId, $search, $perPage)
    {
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
}

// This Repository is for Orders module only
