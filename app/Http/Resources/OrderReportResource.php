<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'reference_number' => $this->reference_number,
            'table_number' => $this->table_number,
            'order_type' => $this->orderType->order_type ?? 'Unknown',
            'order_type_id' => $this->order_type_id ?? null,
            'order_status' => $this->orderStatus->order_status ?? 'Unknown',
            'order_status_id' => $this->order_status_id ?? null,
            'sales_status' => $this->sale->salesStatus->sales_status ?? 'Unknown',
            'total_quantity' => $this->total_quantity ?? null,
            'cashier_name' => $this->cashier->cashier_name ?? 'Unknown',
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
