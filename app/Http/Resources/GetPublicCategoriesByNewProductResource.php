<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetPublicCategoriesByNewProductResource extends JsonResource
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
            'product_category_id' => $this->product_category_id,
            'category_label' => $this->category_label,
            'meal_type' => $this->baseCategory->category->meal_type ?? null,
            'product_base_category_id' => $this->product_base_category_id,
        ];
    }
}
