<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'order_id' => $this->order_id,
            'order_number' => $this->order_number,
            'table_number' => $this->table_number,
            'reference_number' => $this->reference_number,
            'total_quantity' => $this->total_quantity,
            'sales_status' => $this->sale->salesStatus->sales_status ?? 'Unknown',
            'order_type' => $this->orderType->order_type ?? 'Unknown',
            'order_type_id' => $this->order_type_id ?? 0,
            'order_status' => $this->orderStatus->order_status ?? 'Unknown',
            'order_status_id' => $this->order_status_id ?? 0,
            'cashier_name' => $this->cashier->cashier_name ?? 'Unknown',
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

// This Resource is for Orders module only