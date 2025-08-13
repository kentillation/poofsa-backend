<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\StationStatusModel;
use App\Models\TransactionOrdersModel;
use App\Models\TransactionModel;
use App\Models\IngredientsModel;
use App\Models\StocksModel;
use App\Models\ProductsModel;
use App\Events\NewOrderSubmitted;

class BaristaController extends Controller
{
    public function getCurrentOrders()
    {
        try {
            $shopId = Auth::user()->shop_id;
            $branchId = Auth::user()->branch_id;
            $currentDate = now()->format('Y-m-d');
            $data = TransactionModel::select(
                'tbl_transaction.transaction_id',
                'tbl_transaction.table_number',
                'tbl_transaction.reference_number',
                'tbl_transaction.order_status_id',
                'tbl_transaction_orders.product_id',
                'tbl_transaction.updated_at',
            )
                ->join('tbl_transaction_orders', 'tbl_transaction.transaction_id', '=', 'tbl_transaction_orders.transaction_id')
                ->where('tbl_transaction.shop_id', $shopId)
                ->where('tbl_transaction.branch_id', $branchId)
                ->whereDate('tbl_transaction.updated_at', $currentDate)
                ->where('tbl_transaction_orders.station_id', 1) // Assuming 1 is the station ID for Barista
                ->orderBy('tbl_transaction.table_number', 'desc')
                ->get();

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

    public function getBaristaProductDetails($transactionId)
    {
        try {
            if (!$transactionId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction ID is required'
                ], 400);
            }

            $shopId = Auth::user()->shop_id;
            $branchId = Auth::user()->branch_id;
            $transaction = TransactionModel::where('transaction_id', $transactionId)
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->with(['orders' => function ($query) {
                    $query->where('station_id', 1); // Assuming 1 is the station ID for Barista
                }])
                ->with(['orders.product.temperature', 'orders.product.size'])
                ->orderBy('table_number')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $formattedOrders = $transaction->orders
                ->filter(function ($order) {
                    return $order->station_id == 1;
                })
                ->map(function ($order) {
                    return [
                        'transaction_id' => $order->transaction_id ?? 'N/A',
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
                'message' => 'Kitchen product details fetched successfully',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'table_number' => $transaction->table_number,
                    'order_status_id' => $transaction->order_status_id,
                    'all_orders' => $formattedOrders,
                    'customer_name' => $transaction->customer_name,
                    'customer_cash' => $transaction->customer_cash,
                    'customer_change' => $transaction->customer_change,
                    'total_quantity' => $transaction->total_quantity,
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

    public function updateBaristaProductStatus(Request $request)
    {
        // Real-time
        event(new NewOrderSubmitted('Wow! Real-time update works!'));
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                'transactionId' => 'required|numeric|exists:tbl_transaction_orders,transaction_id',
                'productId' => 'required|numeric|exists:tbl_transaction_orders,product_id',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $transactionOrders = TransactionOrdersModel::where('transaction_id', $input['transactionId'])
            ->where('product_id', $input['productId'])
            ->first();
            if (!$transactionOrders) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction orders not found'
                ], 404);
            }
            $currentStatus = $transactionOrders->station_status_id;
            $nextStatus = $currentStatus % 2 + 1;
            $transactionOrders->station_status_id = $nextStatus;
            $transactionOrders->updated_at = now();
            $transactionOrders->save();
            if ($nextStatus == 2) {
                $shopId = $transactionOrders->shop_id;
                $branchId = $transactionOrders->branch_id;
                $productId = $transactionOrders->product_id;
                $quantity = $transactionOrders->quantity;
                $productsToDisable = [];
                $ingredients = IngredientsModel::where('product_id', $productId)
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
                            $affectedProducts = IngredientsModel::where('stock_id', $stockId)
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

                // $user = $request->user();
                // event(new NewOrderSubmitted(
                //     $user->shop_id,
                //     $user->branch_id,
                //     $request->transaction_id,
                //     $request->product_id,
                //     $request->new_status
                // ));
            }

            $transaction = TransactionModel::where('transaction_id', $input['transactionId'])->first();
            if (!$transaction) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }
            $transaction->updated_at = now();
            $transaction->save();

            return response()->json([
                'status' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'transaction_id' => $transactionOrders->transaction_id
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
