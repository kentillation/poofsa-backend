<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_name' => $this->products->product_name ?? null,
            'size_label' => $this->products->size->size_label ?? null,
            'temp_label' => $this->products->temperature->temp_label ?? null,
            'modified_type' => $this->modify->modified_type ?? null,
            'modified_type_id' => $this->modified_type_id,
            'description' => $this->description,
            'admin_name' => $this->users->admin_name ?? null,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

// This Resource is for Products History module only