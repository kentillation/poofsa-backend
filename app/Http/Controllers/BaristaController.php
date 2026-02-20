<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\StationStatusModel;
use App\Models\OrderItemsModel;
use App\Models\OrdersModel;
use App\Models\ProductItemsModel;
use App\Models\IngredientsModel;
use App\Models\ProductsModel;
use App\Events\OrderStatusUpdated;
use App\Events\LowStockLevel;

class BaristaController extends Controller
{

    protected function getShopId(): int
    {
        $user = auth('sanctum')->user();
        return $user->shop_id;
    }

    protected function getBranchId(): int
    {
        $user = auth('sanctum')->user();
        return $user->branch_id;
    }

    protected function getUserId(): int
    {
        $user = auth('sanctum')->user();
        return $user->barista_id;
    }

    public function getCurrentOrders()
    {
        try {
            $shopId = $this->getShopId();
            $branchId = $this->getBranchId();
            $currentDate = now()->format('Y-m-d');
            $data = OrdersModel::select(
                'tbl_orders.order_id',
                'tbl_orders.table_number',
                'tbl_orders.reference_number',
                'tbl_orders.order_status_id',
                'tbl_order_items.product_id',
                'tbl_order_items.shop_station_id',
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
                                'shop_station_id' => $r->shop_station_id,
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

            $shopId = $this->getShopId();
            $branchId = $this->getBranchId();

            $orders = OrdersModel::where('order_id', $orderId)
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                // ->with(['orders' => function ($query) { $query->where('shop_station_id', 1);}]) // if order is for Barista only
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
                // ->filter(function ($order) { return $order->shop_station_id == 1; }) // if order is for Barista only
                ->map(function ($order) {
                    return [
                        'order_id' => $order->order_id ?? 'N/A',
                        'shop_station_id' => $order->shop_station_id ?? 'N/A',
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

    // UPDATED
    private function deductStockForOrder(int $orderId, int $shopStationId = 1)
    {
        // Fetch all relevant order items for the station
        $orderItems = OrderItemsModel::where('order_id', $orderId)
            ->where('shop_station_id', $shopStationId)
            ->get();

        $productsToDisable = [];
        $lowStockItems = [];

        foreach ($orderItems as $orderItem) {
            $shopId = $orderItem->shop_id;
            $branchId = $orderItem->branch_id;
            $quantityMultiplier = $orderItem->quantity;

            // Get all ingredients for the product in one query
            $productIngredients = ProductItemsModel::where('product_id', $orderItem->product_id)
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->with('ingredient') // relation to IngredientsModel
                ->get();

            foreach ($productIngredients as $pi) {
                $ingredient = $pi->ingredient;
                if (!$ingredient) continue;

                // Total usage for this ingredient
                $totalUsage = $pi->quantity_required * $quantityMultiplier;

                $previousStock = $ingredient->stock_in;
                $newStock = max(0, $previousStock - $totalUsage);

                // Check if we crossed the low-stock threshold
                if ($previousStock > $ingredient->alert_quantity && $newStock <= $ingredient->alert_quantity) {
                    $lowStockItems[] = [
                        'ingredient_id' => $ingredient->ingredient_id,
                        'remaining_quantity' => $newStock,
                        'alert_quantity' => $ingredient->alert_quantity
                    ];
                }

                $ingredient->stock_in = $newStock;

                // Set availability if low
                if ($newStock <= $ingredient->alert_quantity) {
                    $ingredient->availability_id = 2;

                    // All products that use this ingredient need to be disabled
                    $affectedProducts = ProductItemsModel::where('ingredient_id', $ingredient->ingredient_id)
                        ->where('shop_id', $shopId)
                        ->where('branch_id', $branchId)
                        ->pluck('product_id')
                        ->toArray();

                    $productsToDisable = array_merge($productsToDisable, $affectedProducts);
                }
            }

            // Bulk save ingredient updates
            DB::table('tbl_ingredients')->upsert(
                $productIngredients->map(function ($pi) {
                    return [
                        'ingredient_id' => $pi->ingredient->ingredient_id,
                        'stock_in' => $pi->ingredient->stock_in,
                        'availability_id' => $pi->ingredient->availability_id,
                        'updated_at' => now()
                    ];
                })->toArray(),
                ['ingredient_id'],
                ['stock_in', 'availability_id', 'updated_at']
            );
        }

        // Fire low-stock alerts if any
        if (!empty($lowStockItems)) {
            $shopId = $orderItems->first()->shop_id ?? null;
            $branchId = $orderItems->first()->branch_id ?? null;
            if ($shopId && $branchId) {
                event(new LowStockLevel(
                    $shopId,
                    $branchId,
                    collect($lowStockItems)
                ));
            }
        }

        // Disable affected products in bulk
        if (!empty($productsToDisable)) {
            $productsToDisable = array_unique($productsToDisable);
            ProductsModel::whereIn('product_id', $productsToDisable)
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->update(['availability_id' => 2]);
        }
    }

    // UPDATED
    public function updateOrderStatus(Request $request)
    {
        try {
            $shopId = $this->getShopId();
            $branchId = $this->getBranchId();

            if (!$shopId || !$branchId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'referenceNumber' => 'required|string|exists:tbl_orders,reference_number',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Fetch order with first order item for status checks
            $order = OrdersModel::where('reference_number', $request->referenceNumber)
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->with(['items'])
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // If order is already served
            if ($order->order_status_id == 3) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order is already served!'
                ], 400);
            }

            // Check if kitchen has pending items
            $hasPendingKitchen = $order->items->where('shop_station_id', 2)
                ->where('station_status_id', 1)
                ->isNotEmpty();

            if ($hasPendingKitchen) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kitchen has still pending item(s).'
                ], 400);
            }

            // All validations passed — update order and stock in a transaction
            DB::transaction(function () use ($order) {

                // 1️⃣ Update order status to Served
                $order->order_status_id = 3;
                $order->updated_at = now();
                $order->save();

                // 2️⃣ Deduct stock for Barista items
                $this->deductStockForOrder($order->order_id, 1);

                // 3️⃣ Update station status for Barista items
                OrderItemsModel::where('order_id', $order->order_id)
                    ->where('shop_station_id', 1)
                    ->update(['station_status_id' => 2, 'updated_at' => now()]);
            });

            // 4️⃣ Trigger real-time notification
            $baristaItem = OrderItemsModel::where('order_id', $order->order_id)
                ->where('shop_station_id', 1)
                ->where('station_status_id', 2)
                ->first();

            if ($baristaItem) {
                event(new OrderStatusUpdated(
                    'Table ' . $order->table_number . ' is ready to serve.',
                    $baristaItem->shop_station_id
                ));
            }

            return response()->json([
                'status' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'reference_number' => $order->reference_number,
                    'order_status_id' => $order->order_status_id,
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
