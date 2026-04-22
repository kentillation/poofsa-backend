<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'shop_id' => $this->shop_id,
            'branch_id' => $this->branch_id,
            'product_id' => $this->product_id,
            'temp_id' => $this->temp_id,
            'size_id' => $this->size_id,
            'category_id' => $this->category_id,
            'station_id' => $this->station_id,
            'availability_id' => $this->availability_id,
            'product_name' => $this->product_name,
            'base_price' => $this->base_price,
            'cost_estimate' => $this->cost_estimate,
            'temp_label' => $this->temperature->temp_label ?? null,
            'size_label' => $this->size->size_label ?? null,
            'category_label' => $this->category->category_label ?? null,
            'station_name' => $this->stations->station_name ?? null,
            'availability_label' => $this->availability->availability_label ?? null,
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Add image fields
            'thumbnail_url' => $this->thumbnail_url,
            'standard_image_url' => $this->standard_image_url,
            'image_size_kb' => $this->image_size_kb,
            'has_image' => $this->has_image,
        ];
    }
}

// This Resource is for Products module only
