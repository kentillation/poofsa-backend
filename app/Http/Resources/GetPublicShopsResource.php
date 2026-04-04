<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetPublicShopsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'shop_id' => $this->shop_id ?? $this['shop_id'] ?? null,
            'branch_id' => $this->branch_id ?? $this['branch_id'] ?? null,
            'shop_name' => $this->shop_name ?? $this['shop_name'] ?? null,
            'shop_type' => $this->shop_type ?? $this['shop_type'] ?? null,
            'lowest_price' => $this->lowest_price ?? $this['lowest_price'] ?? null,
            'product_name' => $this->product_name ?? $this['product_name'] ?? null,
            'product_id' => $this->product_id ?? $this['product_id'] ?? null,
            'category_label' => $this->category_label ?? $this['category_label'] ?? null,
        ];
    }
}
