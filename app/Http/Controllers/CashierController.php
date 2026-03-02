<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\OrdersModel;
use App\Models\OrderItemsModel;
use App\Models\VoidOrdersModel;
use App\Events\NewOrderSubmitted;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\QrCode;

class CashierController extends Controller
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
        return $user->cashier_id;
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
                'tbl_order_status.order_status',
                'tbl_order_items.product_id',
                'tbl_orders.payment_mode_id',
                'tbl_orders.total_due',
                'tbl_orders.updated_at',
            )
                ->join('tbl_order_status', 'tbl_orders.order_status_id', '=', 'tbl_order_status.order_status_id')
                ->join('tbl_order_items', 'tbl_orders.order_id', '=', 'tbl_order_items.order_id')
                ->where('tbl_orders.shop_id', $shopId)
                ->where('tbl_orders.branch_id', $branchId)
                ->whereDate('tbl_orders.updated_at', $currentDate)
                ->orderBy('tbl_orders.table_number', 'desc')
                ->get()
                ->unique('order_id'); // Avoid duplicates

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No current orders found!' : 'Current orders fetched successfully!',
                'data' => $data->values() // Reset keys after unique()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching current orders!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
                'tbl_orders.reference_number',
                'tbl_orders.order_status_id',
                'tbl_order_items.station_status_id',
            )
                ->join('tbl_order_items', 'tbl_orders.order_id', '=', 'tbl_order_items.order_id')
                ->where('tbl_orders.reference_number', $input['referenceNumber'])
                ->where('tbl_orders.shop_id', $shopId)
                ->where('tbl_orders.branch_id', $branchId)
                ->first();

            // Check if station has still pending products
            if ($orders->order_status_id == 1) {
                if (OrderItemsModel::where('order_id', $orders->order_id)
                    ->where('station_status_id', 1)
                    ->exists()
                ) {
                    return response()->json([
                        'status' => false,
                        'message' => "Station has still pending products."
                    ], 400);
                } else {
                    $orders->order_status_id = 2; // Move to 'Ready'
                }
            } elseif ($orders->order_status_id == 2) {
                $orders->order_status_id = 3; // Move to 'Served'
            }

            // Check if the order is already Served
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
                    //'previous_status_id' => $currentStatus // Optional: include previous status
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

    public function submitTransaction(Request $request)
    {
        $input = $request->all();
        $products = $input['products'] ?? ($input['transactions'][0]['products'] ?? []);
        $orderData = isset($input['transactions'])
            ? $input['transactions'][0]
            : $input;
        $validator = Validator::make([
            'reference_number' => $orderData['reference_number'] ?? null,
            'total_quantity' => $orderData['total_quantity'] ?? null,
            'customer_charge' => $orderData['customer_charge'] ?? null,
            'total_due' => $orderData['total_due'] ?? null,
            'order_type_id' => $orderData['order_type_id'] ?? null,
            'order_type_charge' => $orderData['order_type_charge'] ?? null,
            'customer_cash' => $orderData['customer_cash'] ?? null,
            'customer_change' => $orderData['customer_change'] ?? null,
            'customer_discount' => $orderData['customer_discount'] ?? null,
            'computed_discount' => $orderData['computed_discount'] ?? null,
            'payment_mode_id' => $orderData['payment_mode_id'] ?? null,
            'table_number' => $orderData['table_number'] ?? null,
            'customer_name' => $orderData['customer_name'] ?? null,
            'order_note' => $orderData['order_note'] ?? null,
            'products' => $products,

        ], [
            'reference_number' => 'required|string|unique:tbl_orders,reference_number',
            'total_quantity' => 'required|integer|min:1',
            'customer_charge' => 'required|numeric|min:0',
            'total_due' => 'required|numeric|min:0',
            'order_type_id' => 'required|integer|min:1',
            'order_type_charge' => 'required|numeric|min:0',
            'customer_cash' => 'required|numeric|min:0',
            'customer_change' => 'required|numeric|min:0',
            'customer_discount' => 'required|numeric|min:0',
            'computed_discount' => 'required|numeric|min:0',
            'payment_mode_id' => 'required|integer|min:1',
            'table_number' => 'required|integer',
            'customer_name' => 'required|string',
            'order_note' => 'required|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.station_id' => 'required|integer|min:1',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            Log::error('Validation error in submitTransaction', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $shopId = $request->user()->shop_id;
            $branchId = $request->user()->branch_id;
            $currentDate = now()->format('Y-m-d');

            // Compute next order_number
            $latestOrder = OrdersModel::where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->whereDate('created_at', $currentDate) // check today’s orders
                ->orderBy('created_at', 'desc')
                ->first();
            if ($latestOrder && $latestOrder->order_number) {
                $lastNumber = $latestOrder->order_number; // 00001 → 1
                $nextOrderNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT); // 00002
            } else {
                $nextOrderNumber = '00001'; // first order of the day
            }

            $orderData = [
                'shop_id' => $user->shop_id,
                'branch_id' => $user->branch_id,
                'reference_number' => $orderData['reference_number'],
                'total_quantity' => $orderData['total_quantity'],
                'customer_charge' => $orderData['customer_charge'],
                'total_due' => $orderData['total_due'],
                'order_type_id' => $orderData['order_type_id'],
                'order_type_charge' => $orderData['order_type_charge'],
                'customer_cash' => $orderData['customer_cash'],
                'customer_change' => $orderData['customer_change'],
                'customer_discount' => $orderData['customer_discount'],
                'computed_discount' => $orderData['computed_discount'],
                'payment_mode_id' => $orderData['payment_mode_id'],
                'table_number' => $orderData['table_number'],
                'customer_name' => $orderData['customer_name'],
                'order_note' => $orderData['order_note'],
                'order_status_id' => 1,
                'order_number' => $nextOrderNumber,
                'user_id' => $user->cashier_id,
            ];

            $orders = DB::transaction(function () use ($orderData, $products) {
                $orders = OrdersModel::create($orderData);

                $orders->orders()->createMany(
                    collect($products)->map(function ($product) use ($orders) {
                        return [
                            'product_id' => $product['product_id'],
                            'station_id' => $product['station_id'],
                            'quantity' => $product['quantity'],
                            'station_status_id' => 1,
                            'shop_id' => $orders->shop_id,
                            'branch_id' => $orders->branch_id,
                        ];
                    })->toArray()
                );
                return $orders;
            });

            // Real-time Update
            $stationsToNotify = array_unique(array_column($products, 'station_id'));
            foreach ($stationsToNotify as $stationId) {
                event(new NewOrderSubmitted(
                    'You have a new order.',
                    $stationId
                ));
            }

            // Generate QR code
            $newReference = $orders->reference_number;
            $qr_text = "https://poofsa-tend.vercel.app/reference/{$newReference}";
            $qr_code = QrCode::create($qr_text)
                ->setSize(100)
                ->setMargin(1);
            $qr_writer = new PngWriter();
            $qr_result = $qr_writer->write($qr_code);
            $qrCodePath = '../../qr-codes/' . $newReference . '.png';
            if (!file_exists(dirname($qrCodePath))) {
                mkdir(dirname($qrCodePath), 0755, true);
            }
            $qr_result->saveToFile($qrCodePath);

            // Low Stock Real-time Update
            // The low stock check MUST be placed immediately after ingredient deduction there.
            // $lowStockItems = StockBatchesModel::select(
            //         'tbl_stock_batches.ingredient_id',
            //         'tbl_ingredients.ingredient_name',
            //         'tbl_ingredients.alert_quantity',
            //         DB::raw('SUM(tbl_stock_batches.quantity_remaining) as total_remaining')
            //     )
            //     ->join('tbl_ingredients', 'tbl_stock_batches.ingredient_id', '=', 'tbl_ingredients.ingredient_id')
            //     ->where('tbl_stock_batches.shop_id', $shopId)
            //     ->where('tbl_stock_batches.branch_id', $branchId)
            //     ->groupBy(
            //         'tbl_stock_batches.ingredient_id',
            //         'tbl_ingredients.ingredient_name',
            //         'tbl_ingredients.alert_quantity'
            //     )
            //     ->havingRaw('SUM(tbl_stock_batches.quantity_remaining) <= tbl_ingredients.alert_quantity')
            //     ->get();

            // if ($lowStockItems->isNotEmpty()) {
            //     event(new LowStockLevel(
            //         $shopId,
            //         $branchId,
            //         $lowStockItems
            //     ));
            // }

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to create order',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function saveVoid(Request $request)
    {
        $request->validate([
            'reference_number' => 'required|string',
            'order_id' => 'required|integer',
            'table_number' => 'required|integer',
            'product_id' => 'required|integer',
            'from_quantity' => 'required|integer',
            'to_quantity' => 'required|integer',
        ]);

        $exists = VoidOrdersModel::where([
            'reference_number' => $request->reference_number,
            'order_id' => $request->order_id,
            'product_id'     => $request->product_id,
        ])->exists();

        if ($exists) {
            return response()->json([
                'status'  => false,
                'message' => 'One-time void only in a product'
            ], 409);
        }

        try {
            DB::transaction(function () use ($request) {
                $userId = $this->getUserId();
                $shopId = $this->getShopId();
                $branchId = $this->getBranchId();

                // Save the void record
                $voidData = new VoidOrdersModel();
                $voidData->reference_number = $request->input('reference_number');
                $voidData->order_id = $request->input('order_id');
                $voidData->table_number = $request->input('table_number');
                $voidData->product_id = $request->input('product_id');
                $voidData->from_quantity = $request->input('from_quantity');
                $voidData->to_quantity = $request->input('to_quantity');
                $voidData->void_status_id = 1;
                $voidData->user_id = $userId;
                $voidData->shop_id = $shopId;
                $voidData->branch_id = $branchId;
                $voidData->save();

                // Get the product price and current order details
                $product = DB::table('tbl_products')
                    ->where('product_id', $request->product_id)
                    ->first(['product_price']);

                if (!$product) {
                    throw new \Exception('Product not found');
                }

                $orders = DB::table('tbl_orders')
                    ->where('order_id', $request->order_id)
                    ->first();

                if (!$orders) {
                    throw new \Exception('Order not found');
                }

                $quantityReduction = $request->from_quantity - $request->to_quantity;
                $amountReduction = $quantityReduction * $product->product_price;

                // Update the order
                $affectedOrder = DB::table('tbl_order_items')
                    ->where('order_id', $request->order_id)
                    ->where('product_id', $request->product_id)
                    ->update([
                        'quantity' => $request->to_quantity,
                        'updated_at' => now()
                    ]);

                if ($affectedOrder === 0) {
                    throw new \Exception('Order not found');
                }

                // Calculate new values based on frontend logic
                $newSubTotal = $orders->total_due / (1 - ($orders->customer_discount / 100));
                $newSubTotalAfterReduction = $newSubTotal - $amountReduction;

                // Apply the same discount percentage to the new subtotal
                $newComputedDiscount = $newSubTotalAfterReduction * ($orders->customer_discount / 100);
                $newTotalDue = $newSubTotalAfterReduction - $newComputedDiscount;

                // Update the main order
                OrdersModel::where('order_id', $request->order_id)
                    ->update([
                        'total_quantity' => DB::raw('total_quantity - ' . $quantityReduction),
                        'customer_charge' => $newSubTotalAfterReduction,
                        'total_due' => $newTotalDue,
                        'computed_discount' => $newComputedDiscount,
                        'customer_change' => DB::raw('customer_cash - ' . $newTotalDue),
                        'updated_at' => now()
                    ]);

                OrderItemsModel::where('order_id', $request->order_id)
                    ->where('quantity', 0)
                    ->delete();

                OrdersModel::where('order_id', $request->order_id)
                    ->where('total_quantity', 0)
                    ->delete();
            });

            return response()->json([
                'status' => true,
                'message' => 'Void created and order updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Void creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to create void order',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
