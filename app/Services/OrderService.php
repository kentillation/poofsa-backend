<?php

namespace App\Services;

use App\Models\OrdersModel;

class OrderService
{
    public static function getOrdersService($shopId, $branchId, $search, $page, $perPage)
    {
        $query = OrdersModel::with(['orderType', 'orderStatus'])
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('orderType', function ($q2) use ($search) {
                        $q2->where('order_type', 'like', "%{$search}%");
                    })
                    ->orWhereHas('orderStatus', function ($q2) use ($search) {
                        $q2->where('order_status', 'like', "%{$search}%");
                    });
            });
        }

        $total = $query->count();

        $orders = $query->orderByDesc('updated_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Map orders for frontend display
        $mapped = $orders->map(function ($order) {
            return [
                'shop_id' => $order->shop_id,
                'branch_id' => $order->branch_id,
                'order_number' => $order->order_number,
                'table_number' => $order->table_number,
                'reference_number' => $order->reference_number,
                'total_quantity' => $order->total_quantity,
                'customer_cash' => $order->customer_cash,
                'customer_change' => $order->customer_change,

                // 'total_amount' => ?,
                // 'payment_method' => ?,
                // 'sales_status' => ?,

                'order_type' => $order->orderType->order_type ?? 'Unknown',
                'order_type_id' => $order->order_type_id ?? null,
                'order_status' => $order->orderStatus->order_status ?? 'Unknown',
                'order_status_id' => $order->order_status_id ?? null,
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'mapped' => $mapped,
            'total' => $total,
        ];
    }
}
