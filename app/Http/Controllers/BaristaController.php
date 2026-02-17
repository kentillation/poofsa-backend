<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\StationStatusModel;
use App\Models\OrderItemsModel;
use App\Models\OrdersModel;
use App\Models\ProductIngredientsModel;
use App\Models\StocksModel;
use App\Models\ProductsModel;
use App\Events\OrderStatusUpdated;

class BaristaController extends Controller
{
    public function getCurrentOrders()
    {
        try {
            $shopId = auth()->user()->shop_id;
            $branchId = auth()->user()->branch_id;
            $currentDate = now()->format('Y-m-d');
            $data = OrdersModel::select(
                'tbl_orders.order_id',
                'tbl_orders.table_number',
                'tbl_orders.reference_number',
                'tbl_orders.order_status_id',
                'tbl_order_items.product_id',
                'tbl_order_items.station_id',
                'tbl_orders.updated_at'
            )
                ->join('tbl_order_items', 'tbl_orders.order_id', '=', 'tbl_order_items.order_id')
                ->where('tbl_orders.shop_id', $shopId)
                ->where('tbl_orders.branch_id', $branchId)
                ->where('tbl_orders.order_status_id', 1)
                ->whereDate('tbl_orders.updated_at', $currentDate)
                ->orderBy('tbl_orders.created_at')
                ->limit(50)
                ->get()
                ->groupBy('reference_number')
                ->map(function ($rows) {
                    $first = $rows->first();
                    return [
                        'order_id' => (int)$first->order_id,
                        'table_number' => (int)$first->table_number,
                        'reference_number' => $first->reference_number,
                        'order_status_id' => (int)$first->order_status_id,
                        'updated_at' => $first->updated_at,
                        'order_items' => $rows->map(function ($r) {
                            return [
                                'product_id' => $r->product_id,
                                'station_id' => $r->station_id,
                            ];
                        })->values()
                    ];
                })->values();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No current orders found!' : 'Current orders fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching current orders!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBaristaProductDetails($orderId)
    {
        try {
            if (!$orderId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order ID is required'
                ], 400);
            }

            $orders = OrdersModel::where('order_id', $orderId)
                ->where('shop_id', auth()->user()->shop_id)
                ->where('branch_id', auth()->user()->branch_id)
                // ->with(['orders' => function ($query) { $query->where('station_id', 1);}]) // if order is for Barista only
                ->with(['orders.product.temperature', 'orders.product.size'])
                ->orderBy('created_at')
                ->first();

            if (!$orders) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $formattedOrders = $orders->orders
                // ->filter(function ($order) { return $order->station_id == 1; }) // if order is for Barista only
                ->map(function ($order) {
                    return [
                        'order_id' => $order->order_id ?? 'N/A',
                        'station_id' => $order->station_id ?? 'N/A',
                        'product_id' => $order->product->product_id ?? 'N/A',
                        'product_name' => $order->product->product_name ?? 'N/A',
                        'temp_label' => $order->product->temperature->temp_label ?? 'N/A',
                        'size_label' => $order->product->size->size_label ?? 'N/A',
                        'quantity' => $order->quantity,
                        'created_at' => $order->created_at,
                        'station_status_id' => $order->station_status_id,
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Barista product details fetched successfully',
                'data' => [
                    'order_id' => $orders->order_id,
                    'table_number' => $orders->table_number,
                    'order_status_id' => $orders->order_status_id,
                    'order_note' => $orders->order_note,
                    'all_orders' => $formattedOrders,
                    'customer_name' => $orders->customer_name,
                    'customer_cash' => $orders->customer_cash,
                    'customer_change' => $orders->customer_change,
                    'total_quantity' => $orders->total_quantity,
                    'total_amount' => $formattedOrders->sum('subtotal')
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching order details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStationStatus()
    {
        try {
            $statuses = StationStatusModel::all();
            return response()->json([
                'status' => true,
                'message' => $statuses->isEmpty() ? 'No station statuses found!' : 'Success!',
                'data' => $statuses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching station statuses!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function deductStockForOrder($orderId, $stationId = 1)
    {
        $orders = OrderItemsModel::where('order_id', $orderId)
            ->where('station_id', $stationId)
            ->get();

        foreach ($orders as $order) {
            $shopId = $order->shop_id;
            $branchId = $order->branch_id;
            $productId = $order->product_id;
            $quantity = $order->quantity;
            $productsToDisable = [];
            $ingredients = ProductIngredientsModel::where('product_id', $productId)
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->select('stock_id', 'unit_usage')
                ->get();
            foreach ($ingredients as $ingredient) {
                $stockId = $ingredient->stock_id;
                $unitUsage = $ingredient->unit_usage;
                $stock = StocksModel::where('stock_id', $stockId)
                    ->where('shop_id', $shopId)
                    ->where('branch_id', $branchId)
                    ->first();
                if ($stock) {
                    $totalUsage = $unitUsage * $quantity;
                    $stock->stock_in -= $totalUsage;
                    $stock->save();
                    if ($stock->stock_in <= $stock->stock_alert_qty) {
                        $stock->availability_id = 2;
                        $stock->save();
                        $affectedProducts = ProductIngredientsModel::where('stock_id', $stockId)
                            ->where('shop_id', $shopId)
                            ->where('branch_id', $branchId)
                            ->pluck('product_id')
                            ->toArray();
                        $productsToDisable = array_merge($productsToDisable, $affectedProducts);
                    }
                }
            }
            if (!empty($productsToDisable)) {
                $productsToDisable = array_unique($productsToDisable);
                ProductsModel::whereIn('product_id', $productsToDisable)
                    ->where('shop_id', $shopId)
                    ->where('branch_id', $branchId)
                    ->update();
            }
        }
    }

    public function updateOrderStatus(Request $request)
    {
        try {
            $shopId = auth()->user()->shop_id;
            $branchId = auth()->user()->branch_id;
            if (!$shopId || !$branchId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                'referenceNumber' => 'required|string|exists:tbl_orders,reference_number',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $orders = OrdersModel::select(
                'tbl_orders.order_id',
                'tbl_orders.table_number',
                'tbl_orders.reference_number',
                'tbl_orders.order_status_id',
                'tbl_order_items.station_status_id',
            )
                ->join('tbl_order_items', 'tbl_orders.order_id', '=', 'tbl_order_items.order_id')
                ->where('tbl_orders.reference_number', $input['referenceNumber'])
                ->where('tbl_orders.shop_id', $shopId)
                ->where('tbl_orders.branch_id', $branchId)
                ->first();

            if ($orders->order_status_id == 1) {
                if (OrderItemsModel::where('order_id', $orders->order_id)
                    ->where('station_status_id', 1)
                    ->where('station_id', 2)
                    ->exists()
                ) {
                    return response()->json([
                        'status' => false,
                        'message' => "Kitchen has still pending item(s)."
                    ], 400);
                } else {
                    $orders->order_status_id = 3; // Move to 'Served'
                    $this->deductStockForOrder($orders->order_id, 1);
                    OrderItemsModel::where('order_id', $orders->order_id)
                    ->where('station_id', 1)
                    ->update(['station_status_id' => 2]);

                    // Real-time
                    $tableNumber = $orders->table_number;
                    $trnsctn_orders = OrderItemsModel::where([
                        'order_id' => (int)$orders->order_id,
                        'station_id' => 1,
                        'station_status_id' => 2,
                    ])->first();
                    if ($trnsctn_orders) {
                        $stationId = $trnsctn_orders->station_id;
                        event(new OrderStatusUpdated('Table ' . $tableNumber . ' is ready to serve.', $stationId));
                    }
                }
            }

            elseif ($orders->order_status_id == 3) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order is already served!'
                ], 400);
            }

            $orders->updated_at = now();
            $orders->save();

            return response()->json([
                'status' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'reference_number' => $orders->reference_number,
                    'order_status_id' => $orders->order_status_id,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Order status update failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to update order status',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Unused
    public function updateBaristaProductStatus(Request $request)
    {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                'transactionId' => 'required|numeric|exists:tbl_order_items,order_id',
                'productId' => 'required|numeric|exists:tbl_order_items,product_id',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $orders = OrderItemsModel::where('order_id', $input['transactionId'])
                ->where('product_id', $input['productId'])
                ->first();
            if (!$orders) {
                return response()->json([
                    'status' => false,
                    'message' => 'Orders not found'
                ], 404);
            }
            $currentStatus = $orders->station_status_id;
            $nextStatus = $currentStatus % 2 + 1;
            $orders->station_status_id = $nextStatus;
            $orders->updated_at = now();
            $orders->save();
            if ($nextStatus == 2) {
                $shopId = $orders->shop_id;
                $branchId = $orders->branch_id;
                $productId = $orders->product_id;
                $quantity = $orders->quantity;
                $productsToDisable = [];
                $ingredients = ProductIngredientsModel::where('product_id', $productId)
                    ->where('shop_id', $shopId)
                    ->where('branch_id', $branchId)
                    ->select('stock_id', 'unit_usage')
                    ->get();
                foreach ($ingredients as $ingredient) {
                    $stockId = $ingredient->stock_id;
                    $unitUsage = $ingredient->unit_usage;
                    $stock = StocksModel::where('stock_id', $stockId)
                        ->where('shop_id', $shopId)
                        ->where('branch_id', $branchId)
                        ->first();
                    if ($stock) {
                        $totalUsage = $unitUsage * $quantity;
                        $stock->stock_in -= $totalUsage;
                        $stock->save();
                        if ($stock->stock_in <= $stock->stock_alert_qty) {
                            $stock->availability_id = 2;
                            $stock->save();
                            $affectedProducts = ProductIngredientsModel::where('stock_id', $stockId)
                                ->where('shop_id', $shopId)
                                ->where('branch_id', $branchId)
                                ->pluck('product_id')
                                ->toArray();
                            $productsToDisable = array_merge($productsToDisable, $affectedProducts);
                        }
                    }
                }
                if (!empty($productsToDisable)) {
                    $productsToDisable = array_unique($productsToDisable);
                    ProductsModel::whereIn('product_id', $productsToDisable)
                        ->where('shop_id', $shopId)
                        ->where('branch_id', $branchId)
                        ->update(['availability_id' => 2]);
                }
            }

            $orders = OrdersModel::where('order_id', $input['transactionId'])->first();
            if (!$orders) {
                return response()->json([
                    'status' => false,
                    'message' => 'Orders not found'
                ], 404);
            }
            $orders->updated_at = now();
            $orders->save();

            // Real-time
            $tableNumber = $orders->table_number;
            $trnsctn_orders = OrderItemsModel::where([
                'order_id' => (int)$input['transactionId'],
                'station_id' => 1,
                'station_status_id' => 2,
            ])->first();
            if ($trnsctn_orders) {
                $stationId = $trnsctn_orders->station_id;
                event(new OrderStatusUpdated('Barista: Order in table ' . $tableNumber . ' is ready.', $stationId));
            }

            return response()->json([
                'status' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'order_id' => $orders->order_id
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Order status update failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to update order status',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
