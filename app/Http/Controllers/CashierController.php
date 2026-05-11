<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductsModel;
use App\Models\CategoryModel;
use App\Models\OrdersModel;
use App\Models\OrderStatusModel;
use App\Models\SalesModel;
use App\Models\OrderItemsModel;
use App\Models\VoidOrdersModel;
use App\Events\NewOrderSubmitted;
// use Endroid\QrCode\Writer\PngWriter;
// use Endroid\QrCode\QrCode;

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
                'tbl_orders.reference_number',
                'tbl_orders.table_number',
                'tbl_orders.order_status_id',
                'tbl_sales.payment_method_id',
                'tbl_sales.total_amount',
                'tbl_orders.updated_at',
                'tbl_order_status.order_status',
                'tbl_order_items.product_id',
            )
                ->join('tbl_sales', 'tbl_orders.order_id', '=', 'tbl_sales.order_id')
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
        $salesData = isset($input['transactions'])
            ? $input['transactions'][0]
            : $input;

        $validator = Validator::make([
            'reference_number' => $orderData['reference_number'] ?? null,
            'total_quantity' => $orderData['total_quantity'] ?? null,
            'total_amount' => $orderData['total_amount'] ?? null,
            'subtotal' => $orderData['subtotal'] ?? null,
            'order_type_id' => $orderData['order_type_id'] ?? null,
            'order_type_charge' => $orderData['order_type_charge'] ?? null,
            'customer_cash' => $orderData['customer_cash'] ?? null,
            'customer_change' => $orderData['customer_change'] ?? null,
            'discount_amount' => $orderData['discount_amount'] ?? null,
            'payment_method_id' => $orderData['payment_method_id'] ?? null,
            'table_number' => $orderData['table_number'] ?? null,
            'customer_name' => $orderData['customer_name'] ?? null,
            'order_note' => $orderData['order_note'] ?? null,
            'products' => $products,

        ], [
            'reference_number' => 'required|string|unique:tbl_orders,reference_number',
            'total_quantity' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'order_type_id' => 'required|integer|min:1',
            'order_type_charge' => 'required|numeric|min:0',
            'customer_cash' => 'required|numeric|min:0',
            'customer_change' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'payment_method_id' => 'required|integer|min:1',
            'table_number' => 'nullable|integer',
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
            $shopId = $this->getShopId();
            $branchId = $this->getBranchId();
            $userId = $this->getUserId();
            $currentDate = now()->format('Y-m-d');

            // Compute next order_number
            $latestOrder = OrdersModel::where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->whereDate('created_at', $currentDate) // check today’s orders
                ->orderBy('order_id', 'desc')
                ->first();
            if ($latestOrder) {
                $lastNumber = $latestOrder->order_number; // 00001 → 1
                $nextOrderNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT); // 00002
            } else {
                $nextOrderNumber = '00001'; // first order of the day
            }

            $orderData = [
                'order_number' => $nextOrderNumber,
                'reference_number' => $orderData['reference_number'],
                'table_number' => $orderData['table_number'],
                'customer_name' => $orderData['customer_name'],
                'customer_cash' => $orderData['customer_cash'],
                'customer_change' => $orderData['customer_change'],
                'order_type_id' => $orderData['order_type_id'],
                'order_status_id' => 1,
                'order_note' => $orderData['order_note'],
                'total_quantity' => $orderData['total_quantity'],
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'user_id' => $userId,
            ];

            $salesData = [
                'receipt_no' => $salesData['reference_number'],
                'order_id' => null, // to be updated after order creation
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'payment_method_id' => $salesData['payment_method_id'],
                'subtotal' => $salesData['subtotal'],
                'discount_amount' => $salesData['discount_amount'],
                // 'tax_amount' => $salesData['tax_amount'],
                'order_type_charge' => $salesData['order_type_charge'],
                'total_amount' => $salesData['total_amount'],
                'sales_status_id' => 1, // Default status ID for new sales
            ];

            DB::transaction(function () use ($orderData, $salesData, $products) {
                $newOrder = OrdersModel::create($orderData);
                SalesModel::create(
                    array_merge(
                        $salesData,
                        ['order_id' => $newOrder->order_id]
                    )
                );
                $newOrder->items()->createMany(
                    collect($products)->map(function ($product) use ($newOrder) {
                        return [
                            'product_id' => $product['product_id'],
                            'shop_station_id' => $product['station_id'],
                            'quantity' => $product['quantity'],
                            'station_status_id' => 1,
                            'shop_id' => $newOrder->shop_id,
                            'branch_id' => $newOrder->branch_id,
                        ];
                    })->toArray()
                );

                return $newOrder;
            });

            // Real-time Update
            $stationsToNotify = array_unique(array_column($products, 'station_id'));
            foreach ($stationsToNotify as $stationId) {
                event(new NewOrderSubmitted(
                    'You have a new order.',
                    $stationId
                ));
            }

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
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
                $newSubTotal = $orders->total_amount / (1 - ($orders->discount_amount / 100));
                $newSubTotalAfterReduction = $newSubTotal - $amountReduction;

                // Apply the same discount percentage to the new subtotal
                $newComputedDiscount = $newSubTotalAfterReduction * ($orders->discount_amount / 100);
                $newTotalDue = $newSubTotalAfterReduction - $newComputedDiscount;

                // Update the main order
                OrdersModel::where('order_id', $request->order_id)
                    ->update([
                        'total_quantity' => DB::raw('total_quantity - ' . $quantityReduction),
                        'total_amount' => $newSubTotalAfterReduction,
                        // 'total_amount' => $newTotalDue,
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

    public function getProducts(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'shop_id' => 'required|integer',
            'branch_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $shopId = $input['shop_id'];
            $branchId = $input['branch_id'];
            $data = ProductsModel::select(
                'tbl_products.branch_id',
                'tbl_products.shop_id',
                'tbl_products.product_id',
                'tbl_products.product_name',
                'tbl_products.base_price',
                'tbl_products.availability_id',
                'tbl_products.station_id',
                'tbl_product_temp.temp_label',
                'tbl_product_size.size_label',
                'tbl_product_category.category_label',
            )
                ->join('tbl_product_temp', 'tbl_products.temp_id', '=', 'tbl_product_temp.product_temp_id')
                ->join('tbl_product_size', 'tbl_products.size_id', '=', 'tbl_product_size.product_size_id')
                ->join('tbl_product_category', 'tbl_products.category_id', '=', 'tbl_product_category.product_category_id')
                ->where('tbl_products.shop_id', $shopId)
                ->where('tbl_products.branch_id', $branchId)
                ->where('tbl_products.availability_id', 1)
                ->orderBy('tbl_products.product_name')
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No products found!' : 'Products fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching products!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductCategories(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'shop_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $shopId = $input['shop_id'];

        try {
            $data = CategoryModel::when($shopId, function ($query) use ($shopId) {
                    $query->where('shop_id', $shopId);
                })
                ->orderBy('category_label', 'asc')
                ->get()
                ->map(function ($data) {
                    return [
                        'shop_id' => $data->shop_id,
                        'product_category_id' => $data->product_category_id,
                        'category_label' => $data->category_label,
                        'product_base_category_id' => $data->product_base_category_id,
                    ];
                });
            return response()->json([
                'success' => true,
                'message' => $data->isEmpty() ? 'No category found!' : 'Categories fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching categories!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getOrderDetails($referenceNumber)
    {
        try {
            if (!$referenceNumber) {
                return response()->json([
                    'status' => false,
                    'message' => 'Reference number is required'
                ], 400);
            }

            $orders = OrdersModel::where('reference_number', $referenceNumber)
                ->with(['orders.product.temperature', 'orders.product.size', 'orders.stationStatus'])
                ->with(['orderStatus'])
                ->first();

            if (!$orders) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $formattedOrders = $orders->orders->map(function ($order) use ($orders) {
                return [
                    'order_id' => $order->order_id ?? 'N/A',
                    'table_number' => $orders->table_number ?? 'N/A',
                    'product_id' => $order->product->product_id ?? 'N/A',
                    'product_name' => $order->product->product_name ?? 'N/A',
                    'temp_label' => $order->product->temperature->temp_label ?? 'N/A',
                    'size_label' => $order->product->size->size_label ?? 'N/A',
                    'quantity' => $order->quantity,
                    'base_price' => $order->product->base_price,
                    'subtotal' => $order->quantity * $order->product->base_price,
                    'station_status_id' => $order->stationStatus->station_status_id ?? 'N/A',
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Order details fetched successfully',
                'data' => [
                    'reference_number' => $orders->reference_number,
                    'table_number' => $orders->table_number,
                    'order_status_id' => $orders->order_status_id,
                    'order_status' => $orders->orderStatus->order_status,
                    'all_orders' => $formattedOrders,
                    'customer_name' => $orders->customer_name,
                    'customer_cash' => $orders->customer_cash,
                    'customer_discount' => $orders->customer_discount,
                    'customer_change' => $orders->customer_change,
                    'total_quantity' => $orders->total_quantity,
                    'total_amount' =>  $orders->total_due,
                    'created_at' => $orders->created_at,
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

    // For Receipt
    public function getOrderDetailsTemp($referenceNumber)
    {
        // Add security layer
        try {
            if (!$referenceNumber) {
                return response()->json([
                    'status' => false,
                    'message' => 'Reference number is required'
                ], 400);
            }
            $orders = OrdersModel::where('reference_number', $referenceNumber)
                ->with(['orders.product.temperature', 'orders.product.size'])
                ->with(['orderStatus'])
                ->with(['shop'])
                ->with(['branch'])
                ->first();

            if (!$orders) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            $formattedOrders = $orders->orders->map(function ($order) {
                return [
                    'product_name' => $order->product->product_name ?? 'N/A',
                    'temp_label' => $order->product->temperature->temp_label ?? 'N/A',
                    'size_label' => $order->product->size->size_label ?? 'N/A',
                    'quantity' => $order->quantity,
                    'base_price' => $order->product->base_price,
                    'subtotal' => $order->quantity * $order->product->base_price,
                    'created_at' => $order->created_at,

                ];
            });
            return response()->json([
                'status' => true,
                'message' => 'Order details fetched successfully',
                'data' => [
                    'shop_name' => $orders->shop->shop_name,
                    'branch_name' => $orders->branch->branch_name,
                    'branch_location' => $orders->branch->branch_location,
                    'reference_number' => $orders->reference_number,
                    'table_number' => $orders->table_number,
                    'order_status_id' => $orders->order_status_id,
                    'order_status' => $orders->orderStatus->order_status,
                    'all_orders' => $formattedOrders,
                    'customer_name' => $orders->customer_name,
                    'customer_cash' => $orders->customer_cash,
                    'customer_discount' => $orders->customer_discount,
                    'customer_change' => $orders->customer_change,
                    'total_quantity' => $orders->total_quantity,
                    'total_amount' =>  $orders->total_due
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

    public function getQRCode($referenceNumber)
    {
        $folderPath = '../../qr-codes/' . $referenceNumber . '.png';
        if (!File::exists($folderPath)) {
            abort(404, 'Image not found');
        }
        return response()->file($folderPath, [
            'Content-Type' => File::mimeType($folderPath),
            'Content-Disposition' => 'inline'
        ]);
    }

    public function getOrderStatus()
    {
        try {
            $data = OrderStatusModel::all();
            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No order statuses found!' : 'Order statuses fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching order statuses!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getVoid()
    {
        $shopId = $this->getShopId();
        $branchId = $this->getBranchId();
        $voids = VoidOrdersModel::select(
            'tbl_orders_void.reference_number',
            'tbl_orders_void.order_id',
            'tbl_orders_void.table_number',
            'tbl_orders_void.void_status_id',
            'tbl_products.product_name',
            'tbl_product_temp.temp_label',
            'tbl_product_size.size_label',
            'tbl_orders_void.from_quantity',
            'tbl_orders_void.to_quantity',
            'tbl_void_status.void_status',
            'tbl_orders_void.updated_at',
        )
            ->join('tbl_products', 'tbl_orders_void.product_id', '=', 'tbl_products.product_id')
            ->join('tbl_product_temp', 'tbl_products.product_temp_id', '=', 'tbl_product_temp.temp_id')
            ->join('tbl_product_size', 'tbl_products.product_size_id', '=', 'tbl_product_size.size_id')
            ->join('tbl_void_status', 'tbl_orders_void.void_status_id', '=', 'tbl_void_status.void_status_id')
            ->where('tbl_orders_void.shop_id', $shopId)
            ->where('tbl_orders_void.branch_id', $branchId)
            ->orderBy('tbl_orders_void.table_number', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Voids fetched successfully',
            'data' => $voids
        ], 200);
    }
}
