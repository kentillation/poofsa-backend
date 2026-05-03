<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
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
            'shop_name' => $this->shop_name,
            'shop_type' => $this->shop_type,
            'shop_owner' => $this->shop_owner,
            'shop_address' => $this->shop_address,
            'shop_email' => $this->shop_email,
            'shop_contact_number' => $this->shop_contact_number,
            'is_active' => $this->is_active,
            'open_at' => $this->open_at ? date('H:i', strtotime($this->open_at)) : null,
            'close_at' => $this->close_at ? date('H:i', strtotime($this->close_at)) : null,
            'is_overnight' => $this->is_overnight,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,

            // Image fields (same as ProductsModel)
            'thumbnail_url' => $this->thumbnail_url,
            'standard_image_url' => $this->standard_image_url,
            'image_size_kb' => $this->image_size_kb,
            'has_image' => $this->has_image,
        ];
    }
}
