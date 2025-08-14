<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopModel;
use App\Models\BranchModel;
use App\Models\ProductsModel;
use App\Models\StocksModel;
use App\Models\TemperatureModel;
use App\Models\SizeModel;
use App\Models\CategoryModel;
use App\Models\AvailabilityModel;
use App\Models\OrderStatusModel;
use App\Models\TransactionModel;
use App\Models\TransactionVoidModel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class OpenController extends Controller
{
    public function getShopName(Request $request)
    {
        $shopId = $request->user()->shop_id;
        $shop = ShopModel::find($shopId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }
        return response()->json(['shop_name' => $shop->shop_name]);
    }

    public function getShopBranches()
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            // $shopId = Auth::guard('api')->user()->shop_id;
            // $branchId = Auth::guard('api')->user()->branch_id;
            $shopId = auth()->user()->shop_id;
            $branchId = auth()->user()->branch_id;
            $branches = BranchModel::where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->where('status_id', 1)
                ->pluck('branch_name');
            return response()->json(['success' => true, 'branches' => $branches]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getBranchDetails($branchName)
    {
        try {
            $branch = BranchModel::where('branch_name', urldecode($branchName))
                ->where('shop_id', auth()->user()->shop_id)
                ->first();
            if (!$branch) {
                return response()->json(['error' => 'Branch not found'], 404);
            }
            return response()->json($branch);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProducts()
    {
        try {
            // $shopId = Auth::guard('api')->user()->shop_id;
            // $branchId = Auth::guard('api')->user()->branch_id;
            $shopId = auth()->user()->shop_id;
            $branchId = auth()->user()->branch_id;
            $data = ProductsModel::select(
                'tbl_products.product_id',
                'tbl_products.product_name',
                'tbl_products.product_price',
                'tbl_products.product_temp_id',
                'tbl_products.product_size_id',
                'tbl_products.updated_at',
                'tbl_products.product_category_id',
                'tbl_products.availability_id',
                'tbl_products.station_id',
                'tbl_temp.temp_label',
                'tbl_size.size_label',
                'tbl_category.category_label',
                'tbl_availability.availability_label',
                'tbl_products.branch_id',
                'tbl_products.shop_id',
            )
                ->join('tbl_temp', 'tbl_products.product_temp_id', '=', 'tbl_temp.temp_id')
                ->join('tbl_size', 'tbl_products.product_size_id', '=', 'tbl_size.size_id')
                ->join('tbl_category', 'tbl_products.product_category_id', '=', 'tbl_category.category_id')
                ->join('tbl_availability', 'tbl_products.availability_id', '=', 'tbl_availability.availability_id')
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

    public function getStocks($branchId)
    {
        try {
            $data = StocksModel::select(
                'tbl_stocks.stock_id',
                'tbl_stocks.stock_ingredient',
                'tbl_stocks.stock_unit',
                'tbl_stocks.stock_in',
                'tbl_stocks.stock_cost_per_unit',
                'tbl_stocks.stock_alert_qty',
                'tbl_stocks.availability_id',
                'tbl_stocks.shop_id',
                'tbl_stocks.branch_id',
                'tbl_stocks.updated_at',
                'tbl_unit.unit_avb',
                'tbl_availability.availability_label'
            )
                ->join('tbl_unit', 'tbl_stocks.stock_unit', '=', 'tbl_unit.unit_id')
                ->join('tbl_availability', 'tbl_stocks.availability_id', '=', 'tbl_availability.availability_id')
                ->where('tbl_stocks.branch_id', $branchId)
                ->orderBy('tbl_stocks.stock_ingredient')
                ->get();

            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No stocks found!' : 'Stocks fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stocks!',
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

            $transaction = TransactionModel::where('reference_number', $referenceNumber)
                ->with(['orders.product.temperature', 'orders.product.size', 'orders.stationStatus'])
                ->with(['orderStatus'])
                ->first();

            if (!$transaction) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Filter out orders with 0 quantity before mapping
            $filteredOrders = $transaction->orders->filter(function ($order) {
                return $order->quantity > 0;
            });

            $formattedOrders = $filteredOrders->map(function ($order) use ($transaction) {
                return [
                    'transaction_order_id' => $order->transaction_order_id ?? 'N/A',
                    'transaction_id' => $order->transaction_id ?? 'N/A',
                    'table_number' => $transaction->table_number ?? 'N/A',
                    'product_id' => $order->product->product_id ?? 'N/A',
                    'product_name' => $order->product->product_name ?? 'N/A',
                    'temp_label' => $order->product->temperature->temp_label ?? 'N/A',
                    'size_label' => $order->product->size->size_label ?? 'N/A',
                    'quantity' => $order->quantity,
                    'product_price' => $order->product->product_price,
                    'subtotal' => $order->quantity * $order->product->product_price,
                    'station_status_id' => $order->stationStatus->station_status_id ?? 'N/A',
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Order details fetched successfully',
                'data' => [
                    'reference_number' => $transaction->reference_number,
                    'table_number' => $transaction->table_number,
                    'order_status_id' => $transaction->order_status_id,
                    'order_status' => $transaction->orderStatus->order_status,
                    'all_orders' => $formattedOrders,
                    'customer_name' => $transaction->customer_name,
                    'customer_cash' => $transaction->customer_cash,
                    'customer_discount' => $transaction->customer_discount,
                    'customer_change' => $transaction->customer_change,
                    'total_quantity' => $transaction->total_quantity,
                    'total_amount' =>  $transaction->customer_charge,
                    'created_at' => $transaction->created_at,
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

    public function getOrderDetailsTemp($referenceNumber)
    {
        try {
            if (!$referenceNumber) {
                return response()->json([
                    'status' => false,
                    'message' => 'Reference number is required'
                ], 400);
            }
            $transaction = TransactionModel::where('reference_number', $referenceNumber)
                ->with(['orders.product.temperature', 'orders.product.size'])
                ->with(['orderStatus'])
                ->with(['shop'])
                ->with(['branch'])
                ->first();

            if (!$transaction) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            $formattedOrders = $transaction->orders->map(function ($order) {
                return [
                    'product_name' => $order->product->product_name ?? 'N/A',
                    'temp_label' => $order->product->temperature->temp_label ?? 'N/A',
                    'size_label' => $order->product->size->size_label ?? 'N/A',
                    'quantity' => $order->quantity,
                    'product_price' => $order->product->product_price,
                    'subtotal' => $order->quantity * $order->product->product_price,
                    'created_at' => $order->created_at,

                ];
            });
            return response()->json([
                'status' => true,
                'message' => 'Order details fetched successfully',
                'data' => [
                    'shop_name' => $transaction->shop->shop_name,
                    'branch_name' => $transaction->branch->branch_name,
                    'branch_location' => $transaction->branch->branch_location,
                    'reference_number' => $transaction->reference_number,
                    'table_number' => $transaction->table_number,
                    'order_status_id' => $transaction->order_status_id,
                    'order_status' => $transaction->orderStatus->order_status,
                    'all_orders' => $formattedOrders,
                    'customer_name' => $transaction->customer_name,
                    'customer_cash' => $transaction->customer_cash,
                    'customer_discount' => $transaction->customer_discount,
                    'customer_change' => $transaction->customer_change,
                    'total_quantity' => $transaction->total_quantity,
                    'total_amount' =>  $transaction->customer_charge
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

    public function getVoid()
    {
        // $shopId = Auth::guard('api')->user()->shop_id;
        // $branchId = Auth::guard('api')->user()->branch_id;
        $shopId = auth()->user()->shop_id;
        $branchId = auth()->user()->branch_id;
        $voids = TransactionVoidModel::select(
            'tbl_transaction_void.reference_number',
            'tbl_transaction_void.transaction_id',
            'tbl_transaction_void.table_number',
            'tbl_transaction_void.void_status_id',
            'tbl_products.product_name',
            'tbl_temp.temp_label',
            'tbl_size.size_label',
            'tbl_transaction_void.from_quantity',
            'tbl_transaction_void.to_quantity',
            'tbl_void_status.void_status',
            'tbl_transaction_void.updated_at',
        )
            ->join('tbl_products', 'tbl_transaction_void.product_id', '=', 'tbl_products.product_id')
            ->join('tbl_temp', 'tbl_products.product_temp_id', '=', 'tbl_temp.temp_id')
            ->join('tbl_size', 'tbl_products.product_size_id', '=', 'tbl_size.size_id')
            ->join('tbl_void_status', 'tbl_transaction_void.void_status_id', '=', 'tbl_void_status.void_status_id')
            ->where('tbl_transaction_void.shop_id', $shopId)
            ->where('tbl_transaction_void.branch_id', $branchId)
            ->orderBy('tbl_transaction_void.table_number', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Voids fetched successfully',
            'data' => $voids
        ], 200);
    }

    public function getQR($referenceNumber)
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

    public function getQRTemp($referenceNumber)
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

    public function getStockNotifQty($branch_id)
    {
        try {
            $shopId = auth()->user()->shop_id;
            // $shopId = Auth::user()->shop_id;
            $count = StocksModel::where('branch_id', $branch_id)
                ->where('shop_id', $shopId)
                ->whereColumn('stock_in', '<=', 'stock_alert_qty')
                ->count();

            return response()->json([
                'status' => true,
                'message' => 'Low stock count fetched successfully!',
                'count' => $count
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching low stock count!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* PRE-DEFINED ITEMS */

    public function getProductTemperatures()
    {
        try {
            $data = TemperatureModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProductSizes()
    {
        try {
            $data = SizeModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProductCategories()
    {
        try {
            $data = CategoryModel::orderBy('category_label', 'asc')->get();
            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No category found!' : 'Categories fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching categories!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductAvailabilities()
    {
        try {
            $data = AvailabilityModel::all();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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

}
