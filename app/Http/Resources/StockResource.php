<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
                'shop_id' => $this->shop_id,
                'branch_id' => $this->branch_id,
                'ingredient_id' => $this->ingredient_id,
                'ingredient_name' => $this->ingredient_name,
                'base_unit_id' => $this->base_unit_id,
                // 'quantity_received' => $this->batches->quantity_received,
                // 'quantity_remaining' => $this->batches->quantity_remaining,
                'alert_quantity' => $this->alert_quantity,
                'availability_id' => $this->availability_id,
                'availability_label' => $this->availability->availability_label ?? null,
                'unit_label' => $this->unit->unit_label ?? null,
                'unit_avb' => $this->unit->unit_avb ?? null,
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            ];
    }
}

// This Resource is for Stocks module only