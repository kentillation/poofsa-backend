<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\TransactionModel;
use App\Models\TransactionOrdersModel;
use App\Models\TransactionVoidModel;
use App\Models\StocksModel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\QrCode;
use App\Events\NewOrderSubmitted;
use App\Events\LowStockLevel;

class CashierController extends Controller
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
                'tbl_order_status.order_status',
                'tbl_transaction_orders.product_id',
                'tbl_transaction.updated_at',
            )
                ->join('tbl_order_status', 'tbl_transaction.order_status_id', '=', 'tbl_order_status.order_status_id')
                ->join('tbl_transaction_orders', 'tbl_transaction.transaction_id', '=', 'tbl_transaction_orders.transaction_id')
                ->where('tbl_transaction.shop_id', $shopId)
                ->where('tbl_transaction.branch_id', $branchId)
                ->whereDate('tbl_transaction.updated_at', $currentDate)
                ->where('tbl_transaction_orders.station_id', 1)
                // ->where('tbl_transaction.order_status_id', 1) // Only pending orders
                ->orderBy('tbl_transaction.table_number', 'desc')
                ->get()
                ->unique('transaction_id'); // Avoid duplicates

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
            $shopId = Auth::user()->shop_id;
            $branchId = Auth::user()->branch_id;
            if (!$shopId || !$branchId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                'referenceNumber' => 'required|string|exists:tbl_transaction,reference_number',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $transaction = TransactionModel::select(
                'tbl_transaction.transaction_id',
                'tbl_transaction.reference_number',
                'tbl_transaction.order_status_id',
                'tbl_transaction_orders.station_status_id',
            )
                ->join('tbl_transaction_orders', 'tbl_transaction.transaction_id', '=', 'tbl_transaction_orders.transaction_id')
                ->where('tbl_transaction.reference_number', $input['referenceNumber'])
                ->where('tbl_transaction.shop_id', $shopId)
                ->where('tbl_transaction.branch_id', $branchId)
                ->first();

            // Check if some product still undone
            if ($transaction->order_status_id == 1) {
                if (TransactionOrdersModel::where('transaction_id', $transaction->transaction_id)
                    ->where('station_status_id', 1)
                    ->exists()) {
                    return response()->json([
                        'status' => false,
                        'message' => "Station still has pending products."
                    ], 400);
                } else {
                    $transaction->order_status_id = 2; // Move to 'Ready'
                }
            }
            
            elseif ($transaction->order_status_id == 2) {
                $transaction->order_status_id = 3; // Move to 'Served'
            }

            // Check if the order is already Served
            elseif ($transaction->order_status_id == 3) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order is already served!'
                ], 400);
            }
            
            $transaction->updated_at = now();
            $transaction->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'reference_number' => $transaction->reference_number,
                    'order_status_id' => $transaction->order_status_id,
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
        $transactionData = isset($input['transactions'])
            ? $input['transactions'][0]
            : $input;
        $validator = Validator::make([
            'reference_number' => $transactionData['reference_number'] ?? null,
            'table_number' => $transactionData['table_number'] ?? null,
            'total_quantity' => $transactionData['total_quantity'] ?? null,
            'customer_cash' => $transactionData['customer_cash'] ?? null,
            'customer_charge' => $transactionData['customer_charge'] ?? null,
            'customer_change' => $transactionData['customer_change'] ?? null,
            'customer_discount' => $transactionData['customer_discount'] ?? null,
            'total_due' => $transactionData['total_due'] ?? null,
            'computed_discount' => $transactionData['computed_discount'] ?? null,
            'products' => $products
        ], [
            'reference_number' => 'required|string|unique:tbl_transaction,reference_number',
            'table_number' => 'required|integer',
            'total_quantity' => 'required|integer|min:1',
            'customer_cash' => 'required|numeric|min:0',
            'customer_charge' => 'required|numeric|min:0',
            'customer_change' => 'required|numeric|min:0',
            'customer_discount' => 'required|numeric|min:0',
            'total_due' => 'required|numeric|min:0',
            'computed_discount' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:tbl_products,product_id',
            'products.*.station_id' => 'required|integer|min:1',
            'products.*.quantity' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $user = $request->user();
            $dbTransactionData = [
                'shop_id' => $user->shop_id,
                'branch_id' => $user->branch_id,
                'reference_number' => $transactionData['reference_number'],
                'table_number' => $transactionData['table_number'],
                'customer_name' => $transactionData['customer_name'],
                'total_quantity' => $transactionData['total_quantity'],
                'customer_cash' => $transactionData['customer_cash'],
                'customer_charge' => $transactionData['customer_charge'],
                'customer_change' => $transactionData['customer_change'],
                'customer_discount' => $transactionData['customer_discount'],
                'total_due' => $transactionData['total_due'],
                'computed_discount' => $transactionData['computed_discount'],
                'order_status_id' => 1,
                'user_id' => $user->cashier_id,
            ];

            $transaction = DB::transaction(function () use ($dbTransactionData, $products) {
                $transaction = TransactionModel::create($dbTransactionData);
                $transactionItems = array_map(function ($product) use ($transaction) {
                    return [
                        'transaction_id' => $transaction->transaction_id,
                        'product_id' => $product['product_id'],
                        'station_id' => $product['station_id'],
                        'quantity' => $product['quantity'],
                        'station_status_id' => 1,
                        'shop_id' => $transaction->shop_id,
                        'branch_id' => $transaction->branch_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $products);
                TransactionOrdersModel::insert($transactionItems);
                return $transaction;
            });
            $newReference = $transaction->reference_number;
            $qr_text = "https://poofsa-tend.vercel.app/reference/{$newReference}";
            $qr_code = new QrCode($qr_text);
            $qr_code -> getSize(300);
            $qr_code -> getMargin(10);
            $qr_writer = new PngWriter();
            $qr_result = $qr_writer->write($qr_code);
            $qrCodePath = '../../qr-codes/' . $newReference . '.png';
            if (!file_exists(dirname($qrCodePath))) {
                mkdir(dirname($qrCodePath), 0755, true);
            }
            $qr_result->saveToFile($qrCodePath);

            // Real-time
            // event(new NewOrderSubmitted('New order received. Reload it!'));

            $count = StocksModel::where('branch_id', $user->branch_id)
                ->where('shop_id', $user->shop_id)
                ->whereColumn('stock_in', '<=', 'stock_alert_qty')
                ->count();
            if ($count) {
                event(new LowStockLevel(
                    $count . ($count == 1 ? ' stock has' : ' stocks have') . ' low level!'
                ));
            }

            return response()->json([
                'status' => true,
                'message' => 'Transaction created successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Transaction creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to create transaction',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function saveVoid(Request $request)
    {
        $request->validate([
            'reference_number' => 'required|string',
            'transaction_id' => 'required|integer',
            'table_number' => 'required|integer',
            'product_id' => 'required|integer',
            'from_quantity' => 'required|integer',
            'to_quantity' => 'required|integer',
        ]);

        $exists = TransactionVoidModel::where([
            'transaction_id' => $request->transaction_id,
            'product_id'     => $request->product_id,
            'reference_number' => $request->reference_number
        ])->exists();

        if ($exists) {
            return response()->json([
                'status'  => false,
                'message' => 'Void is already Ready.'
            ], 409);
        }

        try {
            DB::transaction(function () use ($request) {
                $userId = Auth::user()->cashier_id;
                $shopId = Auth::user()->shop_id;
                $branchId = Auth::user()->branch_id;

                // Save the void record
                $voidData = new TransactionVoidModel();
                $voidData->reference_number = $request->input('reference_number');
                $voidData->transaction_id = $request->input('transaction_id');
                $voidData->table_number = $request->input('table_number');
                $voidData->product_id = $request->input('product_id');
                $voidData->from_quantity = $request->input('from_quantity');
                $voidData->to_quantity = $request->input('to_quantity');
                $voidData->void_status_id = 1;
                $voidData->user_id = $userId;
                $voidData->shop_id = $shopId;
                $voidData->branch_id = $branchId;
                $voidData->save();

                // Get the product price and current transaction details
                $product = DB::table('tbl_products')
                    ->where('product_id', $request->product_id)
                    ->first(['product_price']);

                if (!$product) {
                    throw new \Exception('Product not found');
                }

                $transaction = DB::table('tbl_transaction')
                    ->where('transaction_id', $request->transaction_id)
                    ->first();

                if (!$transaction) {
                    throw new \Exception('Transaction not found');
                }

                $quantityReduction = $request->from_quantity - $request->to_quantity;
                $amountReduction = $quantityReduction * $product->product_price;

                // Update the transaction order
                $affectedOrder = DB::table('tbl_transaction_orders')
                    ->where('transaction_id', $request->transaction_id)
                    ->where('product_id', $request->product_id)
                    ->update([
                        'quantity' => $request->to_quantity,
                        'updated_at' => now()
                    ]);

                if ($affectedOrder === 0) {
                    throw new \Exception('Transaction order not found');
                }

                // Calculate new values based on frontend logic
                $newSubTotal = $transaction->total_due / (1 - ($transaction->customer_discount / 100));
                $newSubTotalAfterReduction = $newSubTotal - $amountReduction;

                // Apply the same discount percentage to the new subtotal
                $newComputedDiscount = $newSubTotalAfterReduction * ($transaction->customer_discount / 100);
                $newTotalDue = $newSubTotalAfterReduction - $newComputedDiscount;

                // Update the main transaction
                DB::table('tbl_transaction')
                    ->where('transaction_id', $request->transaction_id)
                    ->update([
                        'total_quantity' => DB::raw('total_quantity - ' . $quantityReduction),
                        'customer_charge' => $newSubTotalAfterReduction,
                        'total_due' => $newTotalDue,
                        'computed_discount' => $newComputedDiscount,
                        'customer_change' => DB::raw('customer_cash - ' . $newTotalDue),
                        'updated_at' => now()
                    ]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Void created and transaction updated successfully',
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
