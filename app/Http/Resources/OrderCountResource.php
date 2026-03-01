<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderCountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    // public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'total_orders' => $this->resource
        ];
    }
}
