<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetPublicNewProductsResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'base_price' => $this->base_price,
            'temp_label' => $this->temperature->temp_label ?? null,
            'size_label' => $this->size->size_label ?? null,
            'category_label' => $this->category->category_label ?? null,
        ];
    }
}
