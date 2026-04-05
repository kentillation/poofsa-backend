<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetPublicProductsByMealTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'branch_id' => $this->branch_id,
            'shop_id' => $this->shop_id,
            'shop_name' => $this->shop->shop_name ?? null,
            'shop_type' => $this->shop->shop_type ?? null,
            'product_name' => $this->product_name,
            'base_price' => $this->base_price,
            'temp_label' => $this->temperature->temp_label ?? null,
            'size_label' => $this->size->size_label ?? null,
            'category_label' => $this->category->category_label ?? null,
        ];
    }
}

// This resource is for fetching all public products based on meal type with pagination and search functionality