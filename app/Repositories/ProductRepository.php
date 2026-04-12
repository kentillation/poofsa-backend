<?php

namespace App\Repositories;

use App\Models\ProductsModel;
use App\Models\ProductsHistoryModel;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductRepository
{
    public function getProducts($shopId, $branchId, $perPage = 10, $search = null)
    {
        try {
            return ProductsModel::with([
                'temperature',
                'size',
                'category',
                'stations',
                'availability'
            ])
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId)
                ->when($search, function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                        ->orWhereHas('temperature', function ($q2) use ($search) {
                            $q2->where('temp_label', 'like', "%{$search}%");
                        })
                        ->orWhereHas('size', function ($q2) use ($search) {
                            $q2->where('size_label', 'like', "%{$search}%");
                        })
                        ->orWhereHas('category', function ($q2) use ($search) {
                            $q2->where('category_label', 'like', "%{$search}%");
                        })
                        ->orWhereHas('stations', function ($q2) use ($search) {
                            $q2->where('station_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('availability', function ($q2) use ($search) {
                            $q2->where('availability_label', 'like', "%{$search}%");
                        });
                })
                ->orderByDesc('updated_at')
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Products query error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    public function getProductsHistory($shopId, $branchId, $perPage = 10, $search = null)
    {
        try {
            $query = ProductsHistoryModel::with([
                'products',
                'products.size',
                'products.temperature',
                'modify',
                'users'
            ])
                ->where('shop_id', $shopId)
                ->where('branch_id', $branchId);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('products', function ($q2) use ($search) {
                        $q2->where('product_name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('users', function ($q2) use ($search) {
                            $q2->where('admin_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('modify', function ($q2) use ($search) {
                            $q2->where('modified_type', 'like', "%{$search}%");
                        })
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            return $query->orderByDesc('updated_at')
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Products history query error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}

// This Repository is for Products module only
